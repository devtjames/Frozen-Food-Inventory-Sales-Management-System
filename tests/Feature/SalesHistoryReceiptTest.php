<?php

namespace Tests\Feature;

use App\Enums\SaleReversalStockAction;
use App\Enums\StockMovementType;
use App\Exceptions\SaleAlreadyReversedException;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\BusinessSettingsService;
use App\Services\InventoryService;
use App\Services\SaleReversalService;
use App\Services\SalesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class SalesHistoryReceiptTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_sees_all_sales_while_cashier_only_sees_owned_sales(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $otherCashier = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '5', '2500');
        $owned = $this->sell($product, $cashier, '1');
        $other = $this->sell($product, $otherCashier, '1');

        $this->actingAs($administrator)->get(route('admin.sales.index'))->assertOk()->assertSee($owned->transaction_number)->assertSee($other->transaction_number);
        $this->actingAs($cashier)->get(route('cashier.sales.index'))->assertOk()->assertSee($owned->transaction_number)->assertDontSee($other->transaction_number);
        $this->actingAs($cashier)->get(route('cashier.sales.index', ['cashier' => $otherCashier->id]))->assertOk()->assertSee($owned->transaction_number)->assertDontSee($other->transaction_number);
        $this->actingAs($cashier)->get(route('cashier.sales.show', $other))->assertForbidden();
        $this->actingAs($cashier)->get(route('cashier.sales.receipt', $other))->assertForbidden();
        auth()->logout();
        $this->get(route('cashier.sales.index'))->assertRedirect('/login');
    }

    public function test_history_search_and_filters_return_matching_transactions(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $otherCashier = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '5', '2500');
        $cashSale = $this->sell($product, $cashier, '1', 'cash');
        $cardSale = $this->sell($product, $otherCashier, '1', 'card');

        $this->actingAs($administrator)->get(route('admin.sales.index', ['search' => $cashSale->transaction_number]))
            ->assertOk()->assertSee($cashSale->transaction_number)->assertDontSee($cardSale->transaction_number);
        $this->actingAs($administrator)->get(route('admin.sales.index', ['cashier' => $otherCashier->id, 'payment_method' => 'card', 'transaction_status' => 'completed', 'payment_status' => 'paid']))
            ->assertOk()->assertSee($cardSale->transaction_number)->assertDontSee($cashSale->transaction_number);

        app(SaleReversalService::class)->reverse($cardSale, $this->reversalData(SaleReversalStockAction::DoNotRestore), $administrator);
        $this->actingAs($administrator)->get(route('admin.sales.index', ['transaction_status' => 'reversed', 'payment_status' => 'refunded']))
            ->assertOk()->assertSee($cardSale->transaction_number)->assertDontSee($cashSale->transaction_number);
    }

    public function test_sale_details_and_receipt_use_saved_historical_values_and_store_settings(): void
    {
        [$administrator, $cashier, $product] = $this->actors('4500.00');
        $this->receive($product, $administrator, '5', '2500');
        $originalName = $product->name;
        $sale = $this->sell($product, $cashier, '1');
        $product->update(['name' => 'Renamed Product', 'selling_price' => '9000.00']);
        $settings = app(BusinessSettingsService::class)->get();
        $settings->update(['business_name' => 'Arctic Foods', 'address' => '12 Cold Street', 'phone' => '08000000000', 'receipt_footer_message' => 'Thank you for shopping with us.']);

        $this->actingAs($cashier)->get(route('cashier.sales.show', $sale))
            ->assertOk()->assertSee($originalName)->assertDontSee('Renamed Product')->assertSee('4,500.00');
        $this->actingAs($cashier)->get(route('cashier.sales.receipt', $sale))
            ->assertOk()->assertSee('Arctic Foods')->assertSee('12 Cold Street')->assertSee('08000000000')
            ->assertSee('Thank you for shopping with us.')->assertSee($originalName)->assertSee('window.print()', false)->assertSee('receipt-paper', false);
    }

    public function test_administrator_reversal_restores_exact_original_batches_and_records_refund(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $first = $this->receive($product, $administrator, '2', '3000', '2026-10-08T09:00');
        $second = $this->receive($product, $administrator, '5', '3500', '2026-10-09T09:00');
        $sale = $this->sell($product, $cashier, '2.500', 'card');

        $this->actingAs($administrator)->post(route('admin.sales.reversal.store', $sale), $this->reversalData())->assertRedirect(route('admin.sales.show', $sale));

        $this->assertSame('2.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('5.000', $second->batch->refresh()->remaining_quantity);
        $this->assertDatabaseHas('sale_reversals', ['sale_id' => $sale->id, 'stock_action' => 'restore', 'reversed_by' => $administrator->id]);
        $this->assertDatabaseHas('refunds', ['sale_id' => $sale->id, 'amount' => '11250.00', 'method' => 'cash', 'status' => 'completed']);
        $this->assertDatabaseHas('stock_movements', ['movement_type' => StockMovementType::SaleReversal->value, 'quantity_delta' => '2.500']);
        $this->assertDatabaseHas('payments', ['sale_id' => $sale->id, 'amount' => '11250.00']);
        $this->assertDatabaseHas('sales', ['id' => $sale->id, 'transaction_status' => 'completed', 'payment_status' => 'paid']);
    }

    public function test_non_resellable_refund_does_not_restore_or_deduct_stock_again(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $receipt = $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '1');

        app(SaleReversalService::class)->reverse($sale, $this->reversalData(SaleReversalStockAction::DoNotRestore), $administrator);

        $this->assertSame('4.000', $receipt->batch->refresh()->remaining_quantity);
        $this->assertDatabaseHas('sale_reversal_items', ['sale_item_id' => $sale->items->first()->id, 'stock_restored' => false]);
        $this->assertDatabaseMissing('stock_movements', ['movement_type' => StockMovementType::SaleReversal->value]);
        $this->assertDatabaseHas('refunds', ['sale_id' => $sale->id, 'amount' => '4500.00']);
    }

    public function test_reversal_is_idempotent_and_a_sale_cannot_be_reversed_twice(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '1');
        $data = $this->reversalData();
        $service = app(SaleReversalService::class);
        $first = $service->reverse($sale, $data, $administrator);
        $retry = $service->reverse($sale, $data, $administrator);

        $this->assertSame($first->id, $retry->id);
        $this->assertDatabaseCount('sale_reversals', 1);
        $this->assertDatabaseCount('refunds', 1);

        $this->expectException(SaleAlreadyReversedException::class);
        $service->reverse($sale, $this->reversalData(), $administrator);
    }

    public function test_cashiers_cannot_open_or_submit_sale_reversals(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '1');

        $this->actingAs($cashier)->get(route('admin.sales.reversal.create', $sale))->assertForbidden();
        $this->actingAs($cashier)->post(route('admin.sales.reversal.store', $sale), $this->reversalData())->assertForbidden();
        $this->assertDatabaseCount('sale_reversals', 0);
    }

    public function test_reversal_failure_rolls_back_stock_refund_and_audit_records(): void
    {
        [$administrator, $cashier, $product] = $this->actors();
        $receipt = $this->receive($product, $administrator, '5', '2500');
        $sale = $this->sell($product, $cashier, '1');
        StockMovement::creating(fn (StockMovement $movement) => $movement->movement_type === StockMovementType::SaleReversal ? throw new RuntimeException('Simulated reversal ledger failure.') : null);

        try {
            app(SaleReversalService::class)->reverse($sale, $this->reversalData(), $administrator);
            $this->fail('Expected simulated reversal failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated reversal ledger failure.', $exception->getMessage());
        }

        $this->assertSame('4.000', $receipt->batch->refresh()->remaining_quantity);
        $this->assertDatabaseCount('sale_reversals', 0);
        $this->assertDatabaseCount('sale_reversal_items', 0);
        $this->assertDatabaseCount('refunds', 0);
    }

    /** @return array{User, User, Product} */
    private function actors(string $sellingPrice = '4500.00'): array
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->for(Category::factory())->create(['selling_price' => $sellingPrice]);

        return [$administrator, $cashier, $product];
    }

    private function receive(Product $product, User $administrator, string $quantity, string $cost, string $receivedAt = '2026-10-09T10:00')
    {
        return app(InventoryService::class)->receiveStock([
            'idempotency_key' => (string) Str::uuid(), 'product_id' => $product->id, 'quantity_received' => $quantity,
            'purchase_cost_per_kg' => $cost, 'received_at' => $receivedAt, 'supplier_name' => null, 'reference_number' => null, 'notes' => null,
        ], $administrator);
    }

    private function sell(Product $product, User $cashier, string $quantity, string $method = 'card'): Sale
    {
        return app(SalesService::class)->checkout([[
            'product_id' => $product->id, 'selling_price_per_kg' => $product->selling_price,
            'selling_method' => 'kilograms', 'input_value' => $quantity,
        ]], $method, $method === 'cash' ? '100000' : null, (string) Str::uuid(), $cashier);
    }

    /** @return array<string, string> */
    private function reversalData(SaleReversalStockAction $action = SaleReversalStockAction::Restore): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(), 'stock_action' => $action->value, 'refund_method' => 'cash',
            'reason' => 'Customer transaction was entered incorrectly.', 'reversed_at' => '2026-10-10T12:00',
        ];
    }
}
