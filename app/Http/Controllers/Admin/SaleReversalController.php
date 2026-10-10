<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\SaleReversalStockAction;
use App\Exceptions\SaleAlreadyReversedException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSaleReversalRequest;
use App\Models\Sale;
use App\Services\BusinessFormatter;
use App\Services\SaleReversalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SaleReversalController extends Controller
{
    public function create(Sale $sale, BusinessFormatter $formatter): View
    {
        $this->authorize('reverse', $sale);
        abort_if($sale->isReversed(), 409, 'This sale has already been reversed.');
        $sale->load(['items', 'cashier']);

        return view('admin.sales.reverse', [
            'sale' => $sale,
            'formatter' => $formatter,
            'stockActions' => collect(SaleReversalStockAction::cases())->mapWithKeys(fn (SaleReversalStockAction $action): array => [$action->value => $action->label()])->all(),
            'paymentMethods' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])->all(),
            'idempotencyKey' => (string) Str::uuid(),
            'defaultReversedAt' => Carbon::now(config('app.business_timezone'))->format('Y-m-d\TH:i'),
        ]);
    }

    public function store(StoreSaleReversalRequest $request, Sale $sale, SaleReversalService $service): RedirectResponse
    {
        try {
            $reversal = $service->reverse($sale, $request->validated(), $request->user());
        } catch (SaleAlreadyReversedException $exception) {
            return redirect()->route('admin.sales.show', $sale)->withErrors(['reversal' => $exception->getMessage()]);
        }

        $message = $reversal->stock_action->restoresStock()
            ? 'Sale reversed, refund recorded, and original batch quantities restored.'
            : 'Sale reversed and refund recorded without returning stock to sellable inventory.';

        return redirect()->route('admin.sales.show', $sale)->with('success', $message);
    }
}
