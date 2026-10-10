<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\DecimalMath;
use App\Services\InventoryReportService;
use App\Services\InventoryService;
use App\Services\SalesService;
use App\Services\StockLossService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApplicationReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_realistic_inventory_workflow_reconciles_batches_ledger_and_valuation(): void
    {
        $administrator = User::factory()->administrator()->create();
        $cashier = User::factory()->cashier()->create();
        $product = Product::factory()->for(Category::factory())->create(['selling_price' => '4500.00']);
        $inventory = app(InventoryService::class);

        $firstReceipt = $inventory->receiveStock($this->receipt($product, '100', '3000', '2026-10-01T09:00'), $administrator);
        $secondReceipt = $inventory->receiveStock($this->receipt($product, '50', '3500', '2026-10-02T09:00'), $administrator);

        app(SalesService::class)->checkout([
            $this->saleLine($product, '2.500'),
        ], 'cash', '12000', (string) Str::uuid(), $cashier);
        $this->assertSame('97.500', $firstReceipt->batch->refresh()->remaining_quantity);
        $this->assertSame('50.000', $secondReceipt->batch->refresh()->remaining_quantity);

        $this->assertSame(
            [$firstReceipt->batch->id, $secondReceipt->batch->id],
            StockBatch::query()->where('product_id', $product->id)->where('remaining_quantity', '>', 0)
                ->orderBy('received_at')->orderBy('id')->pluck('id')->all(),
        );

        app(SalesService::class)->checkout([
            $this->saleLine($product, '5.000'),
        ], 'cash', '22500', (string) Str::uuid(), $cashier);
        $this->assertSame('92.500', $firstReceipt->batch->refresh()->remaining_quantity);

        $loss = app(StockLossService::class)->record([
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'stock_batch_id' => null,
            'category' => 'spoiled',
            'quantity_lost' => '3',
            'reason' => 'Verified spoiled stock.',
            'description' => null,
            'lost_at' => '2026-10-03T11:00',
        ], $administrator);
        $this->assertSame([$firstReceipt->batch->id], $loss->allocations->pluck('stock_batch_id')->all());
        $this->assertSame('9000.00', $loss->financial_value);
        $this->assertSame('89.500', $firstReceipt->batch->refresh()->remaining_quantity);

        $thirdReceipt = $inventory->receiveStock($this->receipt($product, '30', '3250', '2026-10-05T09:00'), $administrator);

        app(SalesService::class)->checkout([
            $this->saleLine($product, '1.500'),
        ], 'card', null, (string) Str::uuid(), $cashier);

        $this->assertSame('168.000', $inventory->availableQuantity($product));
        $this->assertSame('88.000', $firstReceipt->batch->refresh()->remaining_quantity);
        $this->assertSame('50.000', $secondReceipt->batch->refresh()->remaining_quantity);
        $this->assertSame('30.000', $thirdReceipt->batch->refresh()->remaining_quantity);
        $this->assertSame('536500.00', app(InventoryReportService::class)->summary()['stock_value']);

        $this->assertDatabaseCount('stock_receipts', 3);
        $this->assertDatabaseCount('stock_batches', 3);
        $this->assertDatabaseCount('stock_movements', 7);
        $this->assertNotSame($firstReceipt->id, $secondReceipt->id);
        $this->assertNotSame($secondReceipt->id, $thirdReceipt->id);
        $this->assertNotSame($firstReceipt->batch->id, $secondReceipt->batch->id);
        $this->assertNotSame($secondReceipt->batch->id, $thirdReceipt->batch->id);

        $ledgerBalance = StockMovement::query()
            ->where('product_id', $product->id)
            ->orderBy('id')
            ->pluck('quantity_delta')
            ->reduce(
                fn (string $total, mixed $quantity): string => app(DecimalMath::class)->add($total, (string) $quantity, 3),
                '0.000',
            );

        $this->assertSame('168.000', $ledgerBalance);
        $this->assertSame(
            ['100.000', '50.000', '-2.500', '-5.000', '-3.000', '30.000', '-1.500'],
            StockMovement::query()->where('product_id', $product->id)->orderBy('id')->pluck('quantity_delta')->all(),
        );
    }

    /** @return array<string, mixed> */
    private function receipt(Product $product, string $quantity, string $cost, string $receivedAt): array
    {
        return [
            'idempotency_key' => (string) Str::uuid(),
            'product_id' => $product->id,
            'quantity_received' => $quantity,
            'purchase_cost_per_kg' => $cost,
            'received_at' => $receivedAt,
            'supplier_name' => null,
            'reference_number' => null,
            'notes' => null,
        ];
    }

    /** @return array<string, string> */
    private function saleLine(Product $product, string $quantity): array
    {
        return [
            'product_id' => (string) $product->id,
            'selling_price_per_kg' => $product->selling_price,
            'selling_method' => 'kilograms',
            'input_value' => $quantity,
        ];
    }
}
