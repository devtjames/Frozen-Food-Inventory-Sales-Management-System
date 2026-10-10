<?php

namespace App\Data;

use App\Models\StockAllocation;
use Illuminate\Support\Collection;

class FifoAllocationResult
{
    /** @param Collection<int, StockAllocation> $allocations */
    public function __construct(
        public readonly Collection $allocations,
        public readonly string $totalCost,
    ) {}
}
