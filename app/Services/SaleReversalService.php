<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\RefundStatus;
use App\Enums\SaleReversalStockAction;
use App\Enums\StockMovementType;
use App\Exceptions\SaleAlreadyReversedException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleReversal;
use App\Models\StockBatch;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class SaleReversalService
{
    public function __construct(
        private readonly DecimalMath $math,
        private readonly InventoryAlertService $alerts,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function reverse(Sale $sale, array $attributes, User $administrator): SaleReversal
    {
        if (! $administrator->isAdministrator() || ! $administrator->is_active) {
            throw new LogicException('Only an active administrator can reverse a sale.');
        }

        $idempotencyKey = (string) $attributes['idempotency_key'];

        if ($existing = SaleReversal::query()->where('idempotency_key', $idempotencyKey)->first()) {
            return $this->matchingRetry($existing, $sale);
        }

        try {
            return DB::transaction(function () use ($sale, $attributes, $administrator, $idempotencyKey): SaleReversal {
                $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->getKey());

                if (SaleReversal::query()->where('sale_id', $lockedSale->id)->exists()) {
                    throw new SaleAlreadyReversedException;
                }

                $action = SaleReversalStockAction::from((string) $attributes['stock_action']);
                $method = PaymentMethod::from((string) $attributes['refund_method']);
                $reversedAt = Carbon::parse((string) $attributes['reversed_at'], (string) config('app.business_timezone'))->utc();
                $items = $lockedSale->items()->with('allocations')->orderBy('product_id')->orderBy('id')->get();

                if ($items->isEmpty()) {
                    throw new LogicException('The sale has no item records to reverse.');
                }

                Product::query()->whereKey($items->pluck('product_id')->unique()->sort()->values())->orderBy('id')->lockForUpdate()->get();

                $reversal = SaleReversal::query()->create([
                    'idempotency_key' => $idempotencyKey,
                    'sale_id' => $lockedSale->id,
                    'stock_action' => $action,
                    'reason' => trim((string) $attributes['reason']),
                    'reversed_at' => $reversedAt,
                    'reversed_by' => $administrator->id,
                ]);

                foreach ($items as $item) {
                    if ($action->restoresStock() && $item->allocations->isEmpty()) {
                        throw new LogicException('A sale item has no batch allocation records to restore.');
                    }

                    $reversalItem = $reversal->items()->create([
                        'sale_item_id' => $item->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                        'refund_amount' => $item->item_amount,
                        'purchase_cost_total' => $item->purchase_cost_total,
                        'stock_restored' => $action->restoresStock(),
                    ]);

                    if (! $action->restoresStock()) {
                        continue;
                    }

                    foreach ($item->allocations->sortBy('stock_batch_id') as $allocation) {
                        $batch = StockBatch::query()->lockForUpdate()->findOrFail($allocation->stock_batch_id);
                        $restored = $this->math->add($batch->remaining_quantity, $allocation->quantity, 3);

                        if ($this->math->compare($restored, $batch->original_quantity, 3) > 0) {
                            throw new LogicException('Restoring this sale would exceed a batch original quantity.');
                        }

                        $batch->update(['remaining_quantity' => $restored]);
                    }

                    $reversalItem->movement()->create([
                        'product_id' => $item->product_id,
                        'stock_batch_id' => $item->allocations->count() === 1 ? $item->allocations->first()->stock_batch_id : null,
                        'movement_type' => StockMovementType::SaleReversal,
                        'quantity_delta' => $item->quantity,
                        'occurred_at' => $reversedAt,
                        'recorded_by' => $administrator->id,
                        'reason' => $reversal->reason,
                    ]);
                }

                $reversal->refund()->create([
                    'sale_id' => $lockedSale->id,
                    'amount' => $lockedSale->total_amount,
                    'method' => $method,
                    'status' => RefundStatus::Completed,
                    'refunded_at' => $reversedAt,
                    'recorded_by' => $administrator->id,
                ]);

                if ($action->restoresStock()) {
                    foreach ($items->pluck('product_id')->unique() as $productId) {
                        $this->alerts->sync((int) $productId);
                    }
                }

                return $reversal->load(['sale', 'items.movement', 'refund', 'administrator']);
            });
        } catch (QueryException $exception) {
            if ($existing = SaleReversal::query()->where('idempotency_key', $idempotencyKey)->first()) {
                return $this->matchingRetry($existing, $sale);
            }

            if (SaleReversal::query()->where('sale_id', $sale->getKey())->exists()) {
                throw new SaleAlreadyReversedException;
            }

            throw $exception;
        }
    }

    private function matchingRetry(SaleReversal $reversal, Sale $sale): SaleReversal
    {
        if ($reversal->sale_id !== $sale->getKey()) {
            throw new InvalidArgumentException('The submission key belongs to another sale reversal.');
        }

        return $reversal->load(['sale', 'items.movement', 'refund', 'administrator']);
    }
}
