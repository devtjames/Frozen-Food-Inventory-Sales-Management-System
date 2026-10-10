<?php

namespace App\Services;

use App\Data\FifoAllocationResult;
use App\Exceptions\InsufficientStockException;
use App\Models\Product;
use App\Models\StockAllocation;
use App\Models\StockBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use LogicException;

class FifoStockAllocator
{
    public function __construct(private readonly DecimalMath $math) {}

    public function allocate(
        Product $product,
        string $quantity,
        Model $source,
        Carbon $allocatedAt,
        ?StockBatch $specificBatch = null,
    ): FifoAllocationResult {
        if (DB::transactionLevel() < 1) {
            throw new LogicException('FIFO allocation must run inside a database transaction.');
        }

        if (! $source->exists) {
            throw new LogicException('FIFO allocation requires a persisted source record.');
        }

        Product::query()->lockForUpdate()->findOrFail($product->getKey());
        $requested = $this->math->normalize($quantity, 3);
        $batches = $this->eligibleBatches($product, $specificBatch);
        $available = $this->sumRemaining($batches);

        if ($this->math->compare($available, $requested, 3) < 0) {
            throw new InsufficientStockException($requested, $available);
        }

        $remainingToAllocate = $requested;
        $totalCost = '0.00';
        $allocations = collect();

        foreach ($batches as $batch) {
            if ($this->math->compare($remainingToAllocate, '0.000', 3) === 0) {
                break;
            }

            $allocatedQuantity = $this->math->min($remainingToAllocate, $batch->remaining_quantity, 3);
            $allocationCost = $this->math->multiply(
                $allocatedQuantity,
                3,
                $batch->purchase_cost_per_kg,
                2,
                2,
            );

            $batch->update([
                'remaining_quantity' => $this->math->subtract($batch->remaining_quantity, $allocatedQuantity, 3),
            ]);

            $allocations->push(StockAllocation::query()->create([
                'stock_batch_id' => $batch->id,
                'source_type' => $source->getMorphClass(),
                'source_id' => $source->getKey(),
                'quantity' => $allocatedQuantity,
                'purchase_cost_per_kg' => $batch->purchase_cost_per_kg,
                'total_cost' => $allocationCost,
                'allocated_at' => $allocatedAt,
            ]));

            $totalCost = $this->math->add($totalCost, $allocationCost, 2);
            $remainingToAllocate = $this->math->subtract($remainingToAllocate, $allocatedQuantity, 3);
        }

        return new FifoAllocationResult($allocations, $totalCost);
    }

    /** @return Collection<int, StockBatch> */
    private function eligibleBatches(Product $product, ?StockBatch $specificBatch): Collection
    {
        $query = StockBatch::query()
            ->where('product_id', $product->getKey())
            ->where('remaining_quantity', '>', 0)
            ->lockForUpdate();

        if ($specificBatch) {
            $query->whereKey($specificBatch->getKey());
        } else {
            $query->orderBy('received_at')->orderBy('id');
        }

        return $query->get();
    }

    /** @param Collection<int, StockBatch> $batches */
    private function sumRemaining(Collection $batches): string
    {
        return $batches->reduce(
            fn (string $total, StockBatch $batch): string => $this->math->add($total, $batch->remaining_quantity, 3),
            '0.000',
        );
    }
}
