<?php

namespace App\Http\Controllers\Cashier;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListSalesRequest;
use App\Models\Sale;
use App\Services\BusinessFormatter;
use App\Services\BusinessSettingsService;
use App\Services\SaleHistoryService;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(ListSalesRequest $request, SaleHistoryService $history, BusinessFormatter $formatter): View
    {
        $filters = $request->validated();

        return view('sales.index', [
            'sales' => $history->paginate($request->user(), $filters),
            'filters' => $filters,
            'formatter' => $formatter,
            'routePrefix' => 'cashier.sales',
            'cashiers' => [],
            'paymentMethods' => collect(PaymentMethod::cases())->mapWithKeys(fn (PaymentMethod $method): array => [$method->value => $method->label()])->all(),
        ]);
    }

    public function show(Sale $sale, BusinessFormatter $formatter): View
    {
        $this->authorize('view', $sale);
        $sale->load(['items', 'payment', 'cashier', 'reversal.administrator', 'reversal.refund']);

        return view('sales.show', compact('sale', 'formatter') + ['routePrefix' => 'cashier.sales']);
    }

    public function receipt(Sale $sale, BusinessFormatter $formatter, BusinessSettingsService $settings): View
    {
        $this->authorize('view', $sale);
        $sale->load(['items', 'payment', 'cashier', 'reversal.refund']);

        return view('sales.receipt', compact('sale', 'formatter') + [
            'profile' => $settings->receiptProfile(),
            'routePrefix' => 'cashier.sales',
        ]);
    }
}
