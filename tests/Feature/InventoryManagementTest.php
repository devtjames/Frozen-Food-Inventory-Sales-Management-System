<?php

namespace Tests\Feature;

use App\Enums\StockMovementType;
use App\Exceptions\InsufficientStockException;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class InventoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_stock_receipt_creates_independent_batch_movement_and_exact_cost(): void
    {
        [$administrator, $product] = $this->inventoryActors();

        $receipt = app(InventoryService::class)->receiveStock($this->receiptPayload($product, [
            'quantity_received' => '100.000',
            'purchase_cost_per_kg' => '3500.00',
        ]), $administrator);

        $this->assertSame('100.000', $receipt->quantity_received);
        $this->assertSame('3500.00', $receipt->purchase_cost_per_kg);
        $this->assertSame('350000.00', $receipt->total_purchase_cost);
        $this->assertSame('100.000', $receipt->batch->original_quantity);
        $this->assertSame('100.000', $receipt->batch->remaining_quantity);
        $this->assertSame(StockMovementType::StockReceived, $receipt->movement->movement_type);
        $this->assertSame('100.000', $receipt->movement->quantity_delta);
        $this->assertSame($administrator->id, $receipt->recorded_by);
        $this->assertSame('100.000', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_additional_receipts_remain_independent_and_increase_authoritative_balance(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $inventory = app(InventoryService::class);
        $first = $inventory->receiveStock($this->receiptPayload($product, ['quantity_received' => '100']), $administrator);
        $second = $inventory->receiveStock($this->receiptPayload($product, [
            'quantity_received' => '50',
            'purchase_cost_per_kg' => '3750.00',
            'received_at' => '2026-10-10T10:00',
        ]), $administrator);

        $this->assertNotSame($first->id, $second->id);
        $this->assertNotSame($first->batch->id, $second->batch->id);
        $this->assertSame('100.000', $first->fresh()->quantity_received);
        $this->assertSame('150.000', $inventory->availableQuantity($product));
        $this->assertDatabaseCount('stock_receipts', 2);
        $this->assertDatabaseCount('stock_batches', 2);
        $this->assertDatabaseCount('stock_movements', 2);
    }

    public function test_fractional_quantities_and_purchase_cost_rounding_are_decimal_safe(): void
    {
        [$administrator, $product] = $this->inventoryActors();

        $receipt = app(InventoryService::class)->receiveStock($this->receiptPayload($product, [
            'quantity_received' => '1.125',
            'purchase_cost_per_kg' => '3333.33',
        ]), $administrator);

        $this->assertSame('1.125', $receipt->quantity_received);
        $this->assertSame('3750.00', $receipt->total_purchase_cost);
        $this->assertSame('1.125', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_fifo_negative_adjustment_uses_oldest_batches_and_preserves_costs(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $inventory = app(InventoryService::class);
        $first = $inventory->receiveStock($this->receiptPayload($product, [
            'quantity_received' => '20',
            'purchase_cost_per_kg' => '3000',
            'received_at' => '2026-10-08T09:00',
        ]), $administrator);
        $second = $inventory->receiveStock($this->receiptPayload($product, [
            'quantity_received' => '30',
            'purchase_cost_per_kg' => '3500',
            'received_at' => '2026-10-09T09:00',
        ]), $administrator);

        $adjustment = $inventory->adjustStock($this->adjustmentPayload($product, [
            'type' => 'negative',
            'quantity' => '25',
            'cost_basis_per_kg' => null,
        ]), $administrator);

        $this->assertSame('0.000', $first->batch->refresh()->remaining_quantity);
        $this->assertSame('25.000', $second->batch->refresh()->remaining_quantity);
        $this->assertSame('25.000', $inventory->availableQuantity($product));
        $this->assertSame('77500.00', $adjustment->total_cost_impact);
        $this->assertSame('-25.000', $adjustment->movement->quantity_delta);
        $this->assertSame(['20.000', '5.000'], $adjustment->allocations->pluck('quantity')->all());
        $this->assertSame(['3000.00', '3500.00'], $adjustment->allocations->pluck('purchase_cost_per_kg')->all());
    }

    public function test_positive_adjustment_creates_costed_batch_and_movement(): void
    {
        [$administrator, $product] = $this->inventoryActors();

        $adjustment = app(InventoryService::class)->adjustStock($this->adjustmentPayload($product, [
            'type' => 'positive',
            'quantity' => '2.500',
            'cost_basis_per_kg' => '3200.00',
        ]), $administrator);

        $this->assertSame('8000.00', $adjustment->total_cost_impact);
        $this->assertSame('2.500', $adjustment->batch->remaining_quantity);
        $this->assertSame('2.500', $adjustment->movement->quantity_delta);
        $this->assertSame(StockMovementType::PositiveAdjustment, $adjustment->movement->movement_type);
        $this->assertSame('2.500', app(InventoryService::class)->availableQuantity($product));
    }

    public function test_insufficient_stock_rolls_back_negative_adjustment_without_partial_changes(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $inventory = app(InventoryService::class);
        $receipt = $inventory->receiveStock($this->receiptPayload($product, ['quantity_received' => '10']), $administrator);

        try {
            $inventory->adjustStock($this->adjustmentPayload($product, [
                'type' => 'negative',
                'quantity' => '10.001',
                'cost_basis_per_kg' => null,
            ]), $administrator);
            $this->fail('Expected insufficient stock protection.');
        } catch (InsufficientStockException $exception) {
            $this->assertSame('10.001', $exception->requestedQuantity);
            $this->assertSame('10.000', $exception->availableQuantity);
        }

        $this->assertSame('10.000', $receipt->batch->refresh()->remaining_quantity);
        $this->assertDatabaseCount('stock_adjustments', 0);
        $this->assertDatabaseCount('stock_allocations', 0);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_serialized_deductions_cannot_drive_stock_negative(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $inventory = app(InventoryService::class);
        $receipt = $inventory->receiveStock($this->receiptPayload($product, ['quantity_received' => '10']), $administrator);
        $inventory->adjustStock($this->adjustmentPayload($product, [
            'type' => 'negative', 'quantity' => '7', 'cost_basis_per_kg' => null,
        ]), $administrator);

        $this->expectException(InsufficientStockException::class);

        try {
            $inventory->adjustStock($this->adjustmentPayload($product, [
                'type' => 'negative', 'quantity' => '4', 'cost_basis_per_kg' => null,
            ]), $administrator);
        } finally {
            $this->assertSame('3.000', $receipt->batch->refresh()->remaining_quantity);
            $this->assertSame('3.000', $inventory->availableQuantity($product));
            $this->assertDatabaseCount('stock_adjustments', 1);
        }
    }

    public function test_idempotency_key_prevents_duplicate_receipt_submission(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $inventory = app(InventoryService::class);
        $payload = $this->receiptPayload($product, ['quantity_received' => '12.500']);

        $first = $inventory->receiveStock($payload, $administrator);
        $second = $inventory->receiveStock($payload, $administrator);

        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('stock_receipts', 1);
        $this->assertDatabaseCount('stock_batches', 1);
        $this->assertDatabaseCount('stock_movements', 1);
        $this->assertSame('12.500', $inventory->availableQuantity($product));
    }

    public function test_failed_movement_write_rolls_back_receipt_and_batch(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        StockMovement::creating(fn () => throw new RuntimeException('Simulated ledger failure.'));

        try {
            app(InventoryService::class)->receiveStock($this->receiptPayload($product), $administrator);
            $this->fail('Expected simulated failure.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated ledger failure.', $exception->getMessage());
        }

        $this->assertDatabaseCount('stock_receipts', 0);
        $this->assertDatabaseCount('stock_batches', 0);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_inventory_pages_are_administrator_only_and_render_responsively(): void
    {
        [$administrator, $product] = $this->inventoryActors();
        $cashier = User::factory()->cashier()->create();

        $this->actingAs($administrator)->get('/admin/inventory')->assertOk()->assertSee('Available stock')->assertSee('md:hidden', false);
        $this->actingAs($administrator)->get('/admin/inventory?stock_status=low')->assertOk()->assertSee($product->name);
        $this->actingAs($administrator)->get('/admin/inventory?stock_status=in_stock')->assertOk()->assertDontSee($product->name);
        $this->actingAs($administrator)->get('/admin/inventory/receipts/create?product='.$product->id)->assertOk()->assertSee('Calculated total purchase cost');
        $this->actingAs($administrator)->get('/admin/inventory/movements')->assertOk();
        $this->actingAs($administrator)->get('/admin/inventory/adjustments')->assertOk();

        $this->app['auth']->logout();
        $this->get('/admin/inventory')->assertRedirect('/login');
        $this->actingAs($cashier)->get('/admin/inventory')->assertForbidden();
        $this->actingAs($cashier)->post('/admin/inventory/receipts', $this->receiptPayload($product))->assertForbidden();
        $this->actingAs($cashier)->post('/admin/inventory/adjustments', $this->adjustmentPayload($product))->assertForbidden();
    }

    /** @return array{User, Product} */
    private function inventoryActors(): array
    {
        $administrator = User::factory()->administrator()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create();

        return [$administrator, $product];
    }

    /** @return array<string, mixed> */
    private function receiptPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'quantity_received' => '100.000',
            'purchase_cost_per_kg' => '3500.00',
            'supplier_name' => 'Cold Chain Supplier',
            'received_at' => '2026-10-09T10:00',
            'reference_number' => 'DEL-1001',
            'notes' => 'Initial delivery.',
        ], $overrides);
    }

    /** @return array<string, mixed> */
    private function adjustmentPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'type' => 'positive',
            'quantity' => '1.000',
            'cost_basis_per_kg' => '3000.00',
            'reason' => 'Verified physical count correction.',
            'adjusted_at' => '2026-10-10T09:00',
            'reference_number' => 'ADJ-1001',
        ], $overrides);
    }
}
