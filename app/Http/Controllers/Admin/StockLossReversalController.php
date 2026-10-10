<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\StockLossAlreadyReversedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStockLossReversalRequest;
use App\Models\StockLoss;
use App\Services\BusinessFormatter;
use App\Services\StockLossService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StockLossReversalController extends Controller
{
    public function create(StockLoss $loss, BusinessFormatter $formatter): View
    {
        $this->authorize('reverse', $loss);
        abort_if($loss->isReversed(), 409, 'This stock loss has already been reversed.');
        $loss->load(['product', 'allocations.batch', 'recorder', 'reversal']);

        return view('admin.inventory.losses.reverse', [
            'loss' => $loss,
            'formatter' => $formatter,
            'idempotencyKey' => (string) Str::uuid(),
            'defaultReversedAt' => Carbon::now(config('app.business_timezone'))->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreStockLossReversalRequest $request, StockLoss $loss, StockLossService $service): RedirectResponse
    {
        try {
            $service->reverse($loss, $request->validated(), $request->user());
        } catch (StockLossAlreadyReversedException $exception) {
            return redirect()->route('admin.inventory.losses.show', $loss)
                ->withErrors(['reversal' => $exception->getMessage()]);
        }

        return redirect()->route('admin.inventory.losses.show', $loss)
            ->with('success', 'Stock loss reversed and allocated quantities restored successfully.');
    }
}
