<?php

namespace App\Services;

use App\Enums\AdminNotificationType;
use App\Enums\StockAdjustmentType;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\StockAdjustment;
use App\Models\StockBatch;
use App\Models\StockReceipt;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FifoStockAllocator $fifoAllocator,
        private readonly InventoryAlertService $alerts,
        private readonly AdminNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function receiveStock(array $attributes, User $recorder): StockReceipt
    {
        $idempotencyKey = (string) $attributes['idempotency_key'];

        if ($existing = StockReceipt::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($attributes, $recorder, $idempotencyKey): StockReceipt {
                if ($existing = StockReceipt::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first()) {
                    return $existing;
                }

                $product = Product::query()->lockForUpdate()->findOrFail($attributes['product_id']);
                $quantity = $this->math->normalize($attributes['quantity_received'], 3);
                $costPerKg = $this->math->normalize($attributes['purchase_cost_per_kg'], 2);
                $totalCost = $this->math->multiply($quantity, 3, $costPerKg, 2, 2);
                $receivedAt = $this->asUtc((string) $attributes['received_at']);

                $receipt = StockReceipt::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $product->id,
                    'quantity_received' => $quantity,
                    'purchase_cost_per_kg' => $costPerKg,
                    'total_purchase_cost' => $totalCost,
                    'supplier_name' => $attributes['supplier_name'] ?? null,
                    'received_at' => $receivedAt,
                    'reference_number' => $attributes['reference_number'] ?? null,
                    'notes' => $attributes['notes'] ?? null,
                    'recorded_by' => $recorder->id,
                ]);

                $batch = StockBatch::query()->create([
                    'product_id' => $product->id,
                    'stock_receipt_id' => $receipt->id,
                    'original_quantity' => $quantity,
                    'remaining_quantity' => $quantity,
                    'purchase_cost_per_kg' => $costPerKg,
                    'total_purchase_cost' => $totalCost,
                    'received_at' => $receivedAt,
                    'reference_number' => $receipt->reference_number,
                    'recorded_by' => $recorder->id,
                ]);

                $receipt->movement()->create([
                    'product_id' => $product->id,
                    'stock_batch_id' => $batch->id,
                    'movement_type' => StockMovementType::StockReceived,
                    'quantity_delta' => $quantity,
                    'occurred_at' => $receivedAt,
                    'recorded_by' => $recorder->id,
                    'reason' => $receipt->notes,
                ]);

                $this->alerts->sync($product);

                return $receipt->load(['product', 'batch', 'movement', 'recorder']);
            });
        } catch (QueryException $exception) {
            if ($existing = StockReceipt::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function adjustStock(array $attributes, User $recorder): StockAdjustment
    {
        $idempotencyKey = (string) $attributes['idempotency_key'];

        if ($existing = StockAdjustment::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($attributes, $recorder, $idempotencyKey): StockAdjustment {
                if ($existing = StockAdjustment::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first()) {
                    return $existing;
                }

                $product = Product::query()->lockForUpdate()->findOrFail($attributes['product_id']);
                $type = StockAdjustmentType::from((string) $attributes['type']);
                $quantity = $this->math->normalize($attributes['quantity'], 3);
                $adjustedAt = $this->asUtc((string) $attributes['adjusted_at']);
                $costBasis = $type === StockAdjustmentType::Positive
                    ? $this->math->normalize($attributes['cost_basis_per_kg'], 2)
                    : null;

                $adjustment = StockAdjustment::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $product->id,
                    'type' => $type,
                    'quantity' => $quantity,
                    'cost_basis_per_kg' => $costBasis,
                    'total_cost_impact' => '0.00',
                    'reason' => $attributes['reason'],
                    'adjusted_at' => $adjustedAt,
                    'reference_number' => $attributes['reference_number'] ?? null,
                    'recorded_by' => $recorder->id,
                ]);

                if ($type === StockAdjustmentType::Positive) {
                    $totalCost = $this->math->multiply($quantity, 3, $costBasis, 2, 2);
                    $batch = StockBatch::query()->create([
                        'product_id' => $product->id,
                        'stock_adjustment_id' => $adjustment->id,
                        'original_quantity' => $quantity,
                        'remaining_quantity' => $quantity,
                        'purchase_cost_per_kg' => $costBasis,
                        'total_purchase_cost' => $totalCost,
                        'received_at' => $adjustedAt,
                        'reference_number' => $adjustment->reference_number,
                        'recorded_by' => $recorder->id,
                    ]);
                    $quantityDelta = $quantity;
                    $movementType = StockMovementType::PositiveAdjustment;
                    $adjustment->update(['total_cost_impact' => $totalCost]);
                } else {
                    $allocationResult = $this->fifoAllocator->allocate($product, $quantity, $adjustment, $adjustedAt);
                    $batch = null;
                    $quantityDelta = $this->math->negate($quantity, 3);
                    $movementType = StockMovementType::NegativeAdjustment;
                    $adjustment->update(['total_cost_impact' => $allocationResult->totalCost]);
                }

                $adjustment->movement()->create([
                    'product_id' => $product->id,
                    'stock_batch_id' => $batch?->id,
                    'movement_type' => $movementType,
                    'quantity_delta' => $quantityDelta,
                    'occurred_at' => $adjustedAt,
                    'recorded_by' => $recorder->id,
                    'reason' => $adjustment->reason,
                ]);

                $this->alerts->sync($product);
                $this->notifications->send(
                    AdminNotificationType::StockAdjustment,
                    'Stock adjustment recorded',
                    ucfirst($type->value).' adjustment of '.$this->displayQuantity($quantity).' for '.$product->name.'.',
                    route('admin.inventory.adjustments.show', $adjustment),
                    $product->id,
                );

                return $adjustment->fresh()->load(['product', 'batch', 'movement', 'allocations.batch', 'recorder']);
            });
        } catch (QueryException $exception) {
            if ($existing = StockAdjustment::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            throw $exception;
        }
    }

    public function availableQuantity(Product|int $product): string
    {
        $productId = $product instanceof Product ? $product->getKey() : $product;

        return StockBatch::query()
            ->where('product_id', $productId)
            ->where('remaining_quantity', '>', 0)
            ->pluck('remaining_quantity')
            ->reduce(
                fn (string $total, mixed $quantity): string => $this->math->add($total, (string) $quantity, 3),
                '0.000',
            );
    }

    /** @param Collection<int, Product> $products */
    public function attachBalances(Collection $products): Collection
    {
        if ($products->isEmpty()) {
            return $products;
        }

        $balances = StockBatch::query()
            ->whereIn('product_id', $products->pluck('id')->all())
            ->where('remaining_quantity', '>', 0)
            ->select('product_id')
            ->selectRaw('SUM(remaining_quantity) as available_quantity')
            ->groupBy('product_id')
            ->get()
            ->mapWithKeys(fn (StockBatch $batch): array => [
                $batch->product_id => $this->math->normalize((string) $batch->available_quantity, 3),
            ]);

        return $products->each(function (Product $product) use ($balances): void {
            $available = $balances->get($product->getKey(), '0.000');
            $product->setAttribute('available_quantity', $available);
            $product->setAttribute(
                'is_low_stock',
                $this->math->compare($available, $product->low_stock_threshold, 3) <= 0,
            );
        });
    }

    private function asUtc(string $value): Carbon
    {
        return Carbon::parse($value, (string) config('app.business_timezone'))->utc();
    }

    private function displayQuantity(string $quantity): string
    {
        return rtrim(rtrim($this->math->normalize($quantity, 3), '0'), '.').' kg';
    }
}
