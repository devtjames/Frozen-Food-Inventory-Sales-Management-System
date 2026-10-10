<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Exceptions\StockLossAlreadyReversedException;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\InventoryService;
use App\Services\StockLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockLossManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_fractional_fifo_loss_deducts_stock_and_calculates_purchase_cost_value(): void
    {
        [$administrator, $product] = $this->actors();
        $first = $this->receive($product, $administrator, '20.000', '3000.00', '2026-10-08T09:00');
        $second = $this->receive($product, $administrator, '30.000', '3500.00', '2026-10-09T09:00');

        $loss = app(StockLossService::class)->record($this->lossPayload($product, [
            'category' => 'spoiled',
            'quantity_lost' => '25.500',
        ]), $administrator);

        $this->assertSame('0.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('24.500', $second->batch->refresh()->remaining_quantity);
        $this->assertSame('24.500', app(InventoryService::class)->availableQuantity($product));
        $this->assertSame('79250.00', $loss->financial_value);
        $this->assertSame(['20.000', '5.500'], $loss->allocations->pluck('quantity')->all());
        $this->assertSame(['60000.00', '19250.00'], $loss->allocations->pluck('total_cost')->all());
        $this->assertSame(StockMovementType::Spoiled, $loss->movement->movement_type);
        $this->assertSame('-25.500', $loss->movement->quantity_delta);
    }

    public function test_known_batch_loss_uses_only_the_selected_batch(): void
    {
        [$administrator, $product] = $this->actors();
        $first = $this->receive($product, $administrator, '20', '3000', '2026-10-08T09:00');
        $second = $this->receive($product, $administrator, '30', '3500', '2026-10-09T09:00');

        $loss = app(StockLossService::class)->record($this->lossPayload($product, [
            'category' => 'damaged',
            'quantity_lost' => '5',
            'stock_batch_id' => $second->batch->id,
        ]), $administrator);

        $this->assertSame('20.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('25.000', $second->batch->refresh()->remaining_quantity);
        $this->assertSame($second->batch->id, $loss->selected_stock_batch_id);
        $this->assertSame([$second->batch->id], $loss->allocations->pluck('stock_batch_id')->all());
        $this->assertSame('17500.00', $loss->financial_value);
        $this->assertSame(StockMovementType::Damaged, $loss->movement->movement_type);
    }

    public function test_insufficient_loss_rolls_back_every_record_and_batch_change(): void
    {
        [$administrator, $product] = $this->actors();
        $receipt = $this->receive($product, $administrator, '8.500', '3000');

        try {
            app(StockLossService::class)->record($this->lossPayload($product, ['quantity_lost' => '8.501']), $administrator);
            $this->fail('Expected insufficient stock protection.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame('8.501', $exception->requestedQuantity);
            $this->assertSame('8.500', $exception->availableQuantity);
        }

        $this->assertSame('8.500', $receipt->batch->refresh()->remaining_quantity);
        $this->assertDatabaseCount('stock_losses', 0);
        $this->assertDatabaseCount('stock_allocations', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_duplicate_submission_does_not_deduct_stock_twice(): void
    {
        [$administrator, $product] = $this->actors();
        $this->receive($product, $administrator, '10', '3000');
        $payload = $this->lossPayload($product, ['quantity_lost' => '2.500']);
        $service = app(StockLossService::class);

        $first = $service->record($payload, $administrator);
        $second = $service->record($payload, $administrator);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('stock_losses', 1);
        $this->assertDatabaseCount('stock_allocations', 1);
        $this->assertSame('7.500', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_reversal_restores_exact_allocated_batches_and_preserves_original_loss(): void
    {
        [$administrator, $product] = $this->actors();
        $first = $this->receive($product, $administrator, '2', '3000', '2026-10-08T09:00');
        $second = $this->receive($product, $administrator, '4', '3500', '2026-10-09T09:00');
        $service = app(StockLossService::class);
        $loss = $service->record($this->lossPayload($product, ['quantity_lost' => '3.500']), $administrator);

        $reversal = $service->reverse($loss, $this->reversalPayload(), $administrator);

        $this->assertSame('2.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('4.000', $second->batch->refresh()->remaining_quantity);
        $this->assertSame('6.000', app(InventoryService::class)->availableQuantity($product));
        $this->assertDatabaseHas('stock_losses', ['id' => $loss->id, 'quantity_lost' => '3.500']);
        $this->assertSame($loss->id, $reversal->stock_loss_id);
        $this->assertSame(StockMovementType::StockLossReversal, $reversal->movement->movement_type);
        $this->assertSame('3.500', $reversal->movement->quantity_delta);
        $this->assertTrue($loss->fresh()->isReversed());
    }

    public function test_a_loss_cannot_be_reversed_more_than_once(): void
    {
        [$administrator, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '3000');
        $service = app(StockLossService::class);
        $loss = $service->record($this->lossPayload($product, ['quantity_lost' => '2']), $administrator);
        $service->reverse($loss, $this->reversalPayload(), $administrator);

        $this->expectException(StockLossAlreadyReversedException::class);

        try {
            $service->reverse($loss, $this->reversalPayload(), $administrator);
        } finally {
            $this->assertDatabaseCount('stock_loss_reversals', 1);
            $this->assertSame('5.000', app(InventoryService::class)->availableQuantity($product));
        }
    }

    public function test_administrator_can_submit_an_authorized_reversal(): void
    {
        [$administrator, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '3000');
        $loss = app(StockLossService::class)->record($this->lossPayload($product, ['quantity_lost' => '2']), $administrator);
        $payload = $this->reversalPayload();

        $this->actingAs($administrator)
            ->post(route('admin.inventory.losses.reversal.store', $loss), $payload)
            ->assertRedirect(route('admin.inventory.losses.show', $loss));
        $this->actingAs($administrator)
            ->post(route('admin.inventory.losses.reversal.store', $loss), $payload)
            ->assertRedirect(route('admin.inventory.losses.show', $loss));

        $this->assertDatabaseHas('stock_loss_reversals', ['stock_loss_id' => $loss->id]);
        $this->assertDatabaseCount('stock_loss_reversals', 1);
        $this->assertSame('5.000', app(InventoryService::class)->availableQuantity($product));
        $this->actingAs($administrator)->get(route('admin.inventory.losses.show', $loss))->assertOk()->assertSee('Reversed');
    }

    public function test_http_validation_rejects_zero_negative_excess_and_invalid_batch_losses(): void
    {
        [$administrator, $product] = $this->actors();
        $otherProduct = Product::factory()->for($product->category)->create();
        $this->receive($product, $administrator, '5', '3000');
        $otherReceipt = $this->receive($otherProduct, $administrator, '5', '4000');

        $this->actingAs($administrator)->post('/admin/inventory/losses', $this->lossPayload($product, ['quantity_lost' => '0']))->assertSessionHasErrors('quantity_lost');
        $this->actingAs($administrator)->post('/admin/inventory/losses', $this->lossPayload($product, ['quantity_lost' => '-1']))->assertSessionHasErrors('quantity_lost');
        $this->actingAs($administrator)->post('/admin/inventory/losses', $this->lossPayload($product, ['quantity_lost' => '5.001']))->assertSessionHasErrors('quantity_lost');
        $this->actingAs($administrator)->post('/admin/inventory/losses', $this->lossPayload($product, ['product_id' => 999999]))->assertSessionHasErrors('product_id');
        $this->actingAs($administrator)->post('/admin/inventory/losses', $this->lossPayload($product, ['stock_batch_id' => $otherReceipt->batch->id]))->assertSessionHasErrors('stock_batch_id');

        $this->assertDatabaseCount('stock_losses', 0);
        $this->assertSame('5.000', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_history_supports_search_filters_status_and_responsive_rendering(): void
    {
        [$administrator, $product] = $this->actors();
        $this->receive($product, $administrator, '5', '3000');
        $loss = app(StockLossService::class)->record($this->lossPayload($product, [
            'category' => 'freezer_failure',
            'reason' => 'Extended freezer power failure.',
        ]), $administrator);

        $this->actingAs($administrator)->get('/admin/inventory/losses')->assertOk()->assertSee($product->name)->assertSee('md:hidden', false);
        $this->actingAs($administrator)->get('/admin/inventory/losses/create?product='.$product->id)->assertOk()->assertSee('Resulting balance');
        $this->actingAs($administrator)->get('/admin/inventory/losses?search=power&category=freezer_failure&status=active')->assertOk()->assertSee($product->name);
        $this->actingAs($administrator)->get('/admin/inventory/losses?category=missing')->assertOk()->assertDontSee('Extended freezer power failure.');
        $this->actingAs($administrator)->get('/admin/inventory/losses/'.$loss->id)->assertOk()->assertSee('Batch cost allocations');
        $this->actingAs($administrator)->get('/admin/inventory/losses/'.$loss->id.'/reversal')->assertOk()->assertSee('Restore the exact quantities');
    }

    public function test_stock_loss_management_is_administrator_only(): void
    {
        [$administrator, $product] = $this->actors();
        $cashier = User::factory()->cashier()->create();
        $this->receive($product, $administrator, '5', '3000');

        $this->get('/admin/inventory/losses')->assertRedirect('/login');
        $this->actingAs($cashier)->get('/admin/inventory/losses')->assertForbidden();
        $this->actingAs($cashier)->get('/admin/inventory/losses/create')->assertForbidden();
        $this->actingAs($cashier)->post('/admin/inventory/losses', $this->lossPayload($product))->assertForbidden();
    }

    /** @return array{User, Product} */
    private function actors(): array
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        return [$administrator, $product];
    }

    private function receive(Product $product, User $user, string $quantity, string $cost, string $receivedAt = '2026-10-09T10:00')
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
        ], $user);
    }

    /** @return array<string, mixed> */
    private function lossPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'stock_batch_id' => null,
            'category' => 'damaged',
            'quantity_lost' => '1.000',
            'reason' => 'Packaging damage found during inspection.',
            'description' => 'Recorded after physical verification.',
            'lost_at' => '2026-10-10T09:00',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function reversalPayload(array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'reason' => 'Physical recount confirmed the stock is usable.',
            'reversed_at' => '2026-10-10T12:00',
        ], $overrides);
    }
}
