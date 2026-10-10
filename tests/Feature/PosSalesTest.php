<?php

namespace Tests\Feature;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use App\Enums\StockMovementType;
use App\Exceptions\CheckoutValidationException;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\ProductPriceChangedException;
use App\Livewire\PosTerminal;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use RuntimeException;
use Tests\TestCase;

class PosSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sell_by_amount_preserves_customer_amount_and_records_weight_rounding(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $receipt = $this->receive($product, $administrator, '10', '3000');

        $sale = app(SalesService::class)->checkout([$this->line($product, 'amount', '5000.00')], 'bank_transfer', null, (string) Str::uuid(), $cashier);
        $item = $sale->items->first();

        $this->assertSame('1.111', $item->quantity);
        $this->assertSame('5000.00', $item->item_amount);
        $this->assertSame('4999.50', $item->calculated_value);
        $this->assertSame('0.50', $item->rounding_adjustment);
        $this->assertSame('8.889', $receipt->batch->refresh()->remaining_quantity);
        $this->assertSame('5000.00', $sale->total_amount);
        $this->assertSame('0.50', $sale->total_rounding_adjustment);
        $this->assertSame(PaymentStatus::Paid, $sale->payment_status);
        $this->assertSame(SaleStatus::Completed, $sale->transaction_status);
        $this->assertSame(PaymentMethod::BankTransfer, $sale->payment->method);
        $this->assertSame(StockMovementType::Sale, $item->movement->movement_type);
        $this->assertSame('-1.111', $item->movement->quantity_delta);
    }

    public function test_sell_by_kilograms_calculates_exact_amount_and_fifo_purchase_cost(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $first = $this->receive($product, $administrator, '2', '3000', '2026-10-08T09:00');
        $second = $this->receive($product, $administrator, '5', '3500', '2026-10-09T09:00');

        $sale = app(SalesService::class)->checkout([$this->line($product, 'kilograms', '2.500')], 'card', null, (string) Str::uuid(), $cashier);
        $item = $sale->items->first();

        $this->assertSame('2.500', $item->quantity);
        $this->assertSame('11250.00', $item->item_amount);
        $this->assertSame('0.00', $item->rounding_adjustment);
        $this->assertSame('7750.00', $item->purchase_cost_total);
        $this->assertSame(['2.000', '0.500'], $item->allocations->pluck('quantity')->all());
        $this->assertSame('0.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('4.500', $second->batch->refresh()->remaining_quantity);
    }

    public function test_multiple_products_and_duplicate_product_lines_are_saved_in_one_atomic_sale(): void
    {
        [$administrator, $cashier, $fish] = $this->actors('4000.00');
        $chicken = Product::factory()->for($fish->category)->create(['selling_price' => '4500.00']);
        $this->receive($fish, $administrator, '10', '2500');
        $this->receive($chicken, $administrator, '10', '3000');

        $sale = app(SalesService::class)->checkout([
            $this->line($fish, 'amount', '5000'),
            $this->line($fish, 'kilograms', '0.250'),
            $this->line($chicken, 'amount', '3000'),
        ], 'bank_transfer', null, (string) Str::uuid(), $cashier);

        $this->assertCount(3, $sale->items);
        $this->assertSame('9000.00', $sale->total_amount);
        $this->assertSame('8.500', app(InventoryService::class)->availableQuantity($fish));
        $this->assertSame('9.333', app(InventoryService::class)->availableQuantity($chicken));
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('stock_movements', 5);
    }

    public function test_combined_duplicate_quantity_cannot_exceed_available_stock(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '3', '2500');

        $this->expectException(InsufficientStockException::class);

        try {
            app(SalesService::class)->checkout([
                $this->line($product, 'kilograms', '2'),
                $this->line($product, 'kilograms', '2'),
            ], 'cash', '16000', (string) Str::uuid(), $cashier);
        } finally {
            $this->assertSame('3.000', app(InventoryService::class)->availableQuantity($product));
            $this->assertDatabaseCount('sales', 0);
            $this->assertDatabaseCount('sale_items', 0);
            $this->assertDatabaseCount('payments', 0);
        }
    }

    public function test_checkout_rejects_price_changes_and_disabled_products(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');
        $staleLine = $this->line($product, 'amount', '5000');
        $product->update(['selling_price' => '4500.00']);

        try {
            app(SalesService::class)->checkout([$staleLine], 'cash', null, (string) Str::uuid(), $cashier);
            $this->fail('Expected stale price protection.');
        } catch (ProductPriceChangedException $exception) {
            $this->assertStringContainsString('selling price', $exception->getMessage());
        }

        $freshLine = $this->line($product->refresh(), 'amount', '5000');
        $product->update(['is_active' => false]);

        $this->expectException(CheckoutValidationException::class);

        try {
            app(SalesService::class)->checkout([$freshLine], 'cash', null, (string) Str::uuid(), $cashier);
        } finally {
            $this->assertDatabaseCount('sales', 0);
            $this->assertSame('5.000', app(InventoryService::class)->availableQuantity($product));
        }
    }

    public function test_cash_payment_records_tendered_amount_and_change(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');

        $sale = app(SalesService::class)->checkout([$this->line($product, 'amount', '5000')], 'cash', '6000', (string) Str::uuid(), $cashier);

        $this->assertSame('6000.00', $sale->amount_tendered);
        $this->assertSame('1000.00', $sale->change_due);
        $this->assertSame('5000.00', $sale->payment->amount);
        $this->assertSame('6000.00', $sale->payment->amount_tendered);
        $this->assertSame('1000.00', $sale->payment->change_due);
    }

    public function test_checkout_idempotency_prevents_duplicate_sales_and_deductions(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');
        $key = (string) Str::uuid();
        $service = app(SalesService::class);

        $first = $service->checkout([$this->line($product, 'kilograms', '1')], 'card', null, $key, $cashier);
        $second = $service->checkout([$this->line($product, 'kilograms', '1')], 'card', null, $key, $cashier);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('sales', 1);
        $this->assertDatabaseCount('sale_items', 1);
        $this->assertDatabaseCount('payments', 1);
        $this->assertSame('4.000', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_serialized_cashier_checkouts_cannot_make_stock_negative(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $secondCashier = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '10', '2500');
        $service = app(SalesService::class);
        $service->checkout([$this->line($product, 'kilograms', '7')], 'card', null, (string) Str::uuid(), $cashier);

        $this->expectException(InsufficientStockException::class);

        try {
            $service->checkout([$this->line($product, 'kilograms', '4')], 'card', null, (string) Str::uuid(), $secondCashier);
        } finally {
            $this->assertSame('3.000', app(InventoryService::class)->availableQuantity($product));
            $this->assertDatabaseCount('sales', 1);
        }
    }

    public function test_ledger_failure_rolls_back_sale_payment_allocation_and_stock(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');
        StockMovement::creating(fn (StockMovement $movement) => $movement->movement_type === StockMovementType::Sale ? throw new RuntimeException('Simulated sale ledger failure.') : null);

        try {
            app(SalesService::class)->checkout([$this->line($product, 'kilograms', '1')], 'cash', null, (string) Str::uuid(), $cashier);
            $this->fail('Expected simulated transaction failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated sale ledger failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('sale_items', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('stock_allocations', 0);
        $this->assertSame('5.000', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_livewire_cart_add_update_remove_and_clear_are_server_calculated(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '10', '2500');
        $outputBufferLevel = ob_get_level();

        try {
            Livewire::actingAs($cashier)->test(PosTerminal::class)
                ->set('selectedProductId', (string) $product->id)
                ->set('sellingMethod', 'amount')
                ->set('inputValue', '5000')
                ->call('addItem')
                ->assertSee('Shopping cart')
                ->assertSee('Complete paid sale')
                ->assertDontSee('@endslot', false)
                ->assertSet('cart.0.quantity', '1.111')
                ->assertSet('cart.0.item_amount', '5000.00')
                ->set('cart.0.input_value', '9000')
                ->call('updateItem', 0)
                ->assertSet('cart.0.quantity', '2.000')
                ->call('removeItem', 0)
                ->assertSet('cart', []);
        } finally {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }
        }
    }

    public function test_cashier_can_add_three_kilograms_and_complete_checkout_through_livewire(): void
    {
        [$administrator, $cashier, $product] = $this->actors('5000.00');
        $this->receive($product, $administrator, '50', '3000');
        $outputBufferLevel = ob_get_level();

        try {
            Livewire::actingAs($cashier)->test(PosTerminal::class)
                ->set('selectedProductId', (string) $product->id)
                ->set('sellingMethod', 'kilograms')
                ->set('inputValue', '3')
                ->call('addItem')
                ->assertSet('cart.0.quantity', '3.000')
                ->assertSet('cart.0.item_amount', '15000.00')
                ->assertSee('Complete paid sale')
                ->assertDontSee('@endslot', false)
                ->set('paymentMethod', 'card')
                ->call('checkout')
                ->assertRedirect();
        } finally {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }
        }

        $sale = Sale::query()->sole();
        $this->assertSame($cashier->id, $sale->cashier_id);
        $this->assertSame('15000.00', $sale->total_amount);
        $this->assertSame('47.000', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_cashier_routes_and_completed_sale_access_are_owner_only(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $otherCashier = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '5', '2500');
        $sale = app(SalesService::class)->checkout([$this->line($product, 'kilograms', '1')], 'card', null, (string) Str::uuid(), $cashier);

        $this->get('/cashier/pos')->assertRedirect('/login');
        $this->actingAs($administrator)->get('/cashier/pos')->assertForbidden();
        $this->actingAs($cashier)->get('/cashier/pos')->assertOk()->assertSee('Point of sale');
        $this->actingAs($cashier)->get(route('cashier.sales.show', $sale))->assertOk()->assertSee($sale->transaction_number)->assertDontSee('purchase cost', false);
        $this->actingAs($otherCashier)->get(route('cashier.sales.show', $sale))->assertForbidden();
    }

    public function test_administrator_can_use_the_pos_without_gaining_cashier_route_access(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4000.00');
        $this->receive($product, $administrator, '5', '2500');

        $this->actingAs($administrator)->get(route('admin.pos'))->assertOk()->assertSee('Point of sale');
        $this->actingAs($administrator)->get(route('cashier.pos'))->assertForbidden();

        $outputBufferLevel = ob_get_level();

        try {
            Livewire::actingAs($administrator)->test(PosTerminal::class)
                ->set('selectedProductId', (string) $product->id)
                ->set('sellingMethod', 'kilograms')
                ->set('inputValue', '1.250')
                ->call('addItem')
                ->call('checkout')
                ->assertRedirect();
        } finally {
            while (ob_get_level() > $outputBufferLevel) {
                ob_end_clean();
            }
        }

        $sale = Sale::query()->sole();
        $this->assertSame($administrator->id, $sale->cashier_id);
        $this->assertSame('3.750', app(InventoryService::class)->availableQuantity($product));
    }

    /** @return array{User, User, Product} */
    private function actors(string $sellingPrice): array
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['selling_price' => $sellingPrice]);

        return [$administrator, $cashier, $product];
    }

    private function receive(Product $product, User $administrator, string $quantity, string $cost, string $receivedAt = '2026-10-09T10:00')
    {
        return app(InventoryService::class)->receiveStock([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'quantity_received' => $quantity,
            'purchase_cost_per_kg' => $cost,
            'received_at' => $receivedAt,
            'supplier_name' => null,
            'reference_number' => null,
            'notes' => null,
        ], $administrator);
    }

    /** @return array<string, mixed> */
    private function line(Product $product, string $method, string $input): array
    {
        return [
            'product_id' => $product->id,
            'selling_price_per_kg' => $product->selling_price,
            'selling_method' => $method,
            'input_value' => $input,
        ];
    }
}
