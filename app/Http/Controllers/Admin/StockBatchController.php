<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockBatch;
use App\Services\BusinessFormatter;
use Illuminate\View\View;

class StockBatchController extends Controller
{
    public function __invoke(StockBatch $batch, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $batch);
        $batch->load(['product.category', 'receipt', 'adjustment', 'recorder', 'allocations.source']);

        return view('admin.inventory.batches.show', compact('batch', 'formatter'));
    }
}
