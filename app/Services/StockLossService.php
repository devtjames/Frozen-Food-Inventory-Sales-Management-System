<?php

namespace App\Services;

use App\Enums\AdminNotificationType;
use App\Enums\StockLossCategory;
use App\Enums\StockMovementType;
use App\Exceptions\InvalidStockBatchException;
use App\Exceptions\StockLossAlreadyReversedException;
use App\Models\Product;
use App\Models\StockBatch;
use App\Models\StockLoss;
use App\Models\StockLossReversal;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class StockLossService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly FifoStockAllocator $fifoAllocator,
        private readonly InventoryAlertService $alerts,
        private readonly AdminNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function record(array $attributes, User $recorder): StockLoss
    {
        $idempotencyKey = (string) $attributes['idempotency_key'];

        if ($existing = StockLoss::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($attributes, $recorder, $idempotencyKey): StockLoss {
                if ($existing = StockLoss::query()->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first()) {
                    return $existing;
                }

                $product = Product::query()->lockForUpdate()->findOrFail($attributes['product_id']);
                $selectedBatch = $this->selectedBatch($attributes['stock_batch_id'] ?? null, $product);
                $quantity = $this->math->normalize($attributes['quantity_lost'], 3);
                $category = StockLossCategory::from((string) $attributes['category']);

                if ($this->math->compare($quantity, '0.000', 3) <= 0) {
                    throw new InvalidArgumentException('The loss quantity must be greater than zero.');
                }

                $lostAt = $this->asUtc((string) $attributes['lost_at']);

                $loss = StockLoss::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'product_id' => $product->id,
                    'selected_stock_batch_id' => $selectedBatch?->id,
                    'category' => $category,
                    'quantity_lost' => $quantity,
                    'financial_value' => '0.00',
                    'reason' => $attributes['reason'],
                    'description' => $attributes['description'] ?? null,
                    'lost_at' => $lostAt,
                    'recorded_by' => $recorder->id,
                ]);

                $allocation = $this->fifoAllocator->allocate($product, $quantity, $loss, $lostAt, $selectedBatch);
                $loss->update(['financial_value' => $allocation->totalCost]);

                $loss->movement()->create([
                    'product_id' => $product->id,
                    'stock_batch_id' => $selectedBatch?->id,
                    'movement_type' => $loss->category->movementType(),
                    'quantity_delta' => $this->math->negate($quantity, 3),
                    'occurred_at' => $lostAt,
                    'recorded_by' => $recorder->id,
                    'reason' => $loss->reason,
                ]);

                $this->alerts->sync($product);
                $this->notifications->send(
                    AdminNotificationType::StockLoss,
                    'Stock loss recorded',
                    $this->displayQuantity($quantity).' of '.$product->name.' was recorded as '.$category->label().'.',
                    route('admin.inventory.losses.show', $loss),
                    $product->id,
                );

                return $loss->fresh()->load(['product', 'selectedBatch', 'allocations.batch', 'movement', 'recorder', 'reversal']);
            });
        } catch (QueryException $exception) {
            if ($existing = StockLoss::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function reverse(StockLoss $loss, array $attributes, User $recorder): StockLossReversal
    {
        $idempotencyKey = (string) $attributes['idempotency_key'];

        if ($existing = StockLossReversal::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($loss, $attributes, $recorder, $idempotencyKey): StockLossReversal {
                $lockedLoss = StockLoss::query()->lockForUpdate()->findOrFail($loss->getKey());

                if (StockLossReversal::query()->where('stock_loss_id', $lockedLoss->id)->exists()) {
                    throw new StockLossAlreadyReversedException;
                }

                $product = Product::query()->lockForUpdate()->findOrFail($lockedLoss->product_id);
                $allocations = $lockedLoss->allocations()->orderBy('id')->get();

                if ($allocations->isEmpty()) {
                    throw new LogicException('The stock loss has no allocation records to restore.');
                }

                foreach ($allocations as $allocation) {
                    $batch = StockBatch::query()->lockForUpdate()->findOrFail($allocation->stock_batch_id);
                    $restoredQuantity = $this->math->add($batch->remaining_quantity, $allocation->quantity, 3);

                    if ($this->math->compare($restoredQuantity, $batch->original_quantity, 3) > 0) {
                        throw new LogicException('Restoring this loss would exceed the batch original quantity.');
                    }

                    $batch->update(['remaining_quantity' => $restoredQuantity]);
                }

                $reversedAt = $this->asUtc((string) $attributes['reversed_at']);
                $reversal = StockLossReversal::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'stock_loss_id' => $lockedLoss->id,
                    'reason' => $attributes['reason'],
                    'reversed_at' => $reversedAt,
                    'recorded_by' => $recorder->id,
                ]);

                $reversal->movement()->create([
                    'product_id' => $lockedLoss->product_id,
                    'stock_batch_id' => $allocations->count() === 1 ? $allocations->first()->stock_batch_id : null,
                    'movement_type' => StockMovementType::StockLossReversal,
                    'quantity_delta' => $lockedLoss->quantity_lost,
                    'occurred_at' => $reversedAt,
                    'recorded_by' => $recorder->id,
                    'reason' => $reversal->reason,
                ]);

                $this->alerts->sync($product);
                $this->notifications->send(
                    AdminNotificationType::Activity,
                    'Stock loss reversed',
                    $this->displayQuantity($lockedLoss->quantity_lost).' of '.$product->name.' was restored after an authorized loss correction.',
                    route('admin.inventory.losses.show', $lockedLoss),
                    $product->id,
                );

                return $reversal->load(['loss.product', 'movement', 'recorder']);
            });
        } catch (QueryException $exception) {
            if ($existing = StockLossReversal::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $existing;
            }

            if (StockLossReversal::query()->where('stock_loss_id', $loss->getKey())->exists()) {
                throw new StockLossAlreadyReversedException;
            }

            throw $exception;
        }
    }

    private function selectedBatch(mixed $batchId, Product $product): ?StockBatch
    {
        if ($batchId === null || $batchId === '') {
            return null;
        }

        $batch = StockBatch::query()->lockForUpdate()->find($batchId);

        if (! $batch || $batch->product_id !== $product->id || $this->math->compare($batch->remaining_quantity, '0.000', 3) <= 0) {
            throw new InvalidStockBatchException;
        }

        return $batch;
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
