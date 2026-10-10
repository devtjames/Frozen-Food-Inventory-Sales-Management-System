<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportRequest;
use App\Models\Sale;
use App\Services\BusinessFormatter;
use App\Services\FinancialReportService;
use App\Services\InventoryAlertService;
use App\Services\InventoryReportService;
use App\Services\ReportingPeriodFactory;
use App\Services\SalesAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(
        ReportRequest $request,
        ReportingPeriodFactory $periods,
        FinancialReportService $financials,
        InventoryReportService $inventory,
        InventoryAlertService $alerts,
        SalesAnalyticsService $analytics,
        BusinessFormatter $formatter,
    ): View {
        $filters = $request->validated();
        $today = $periods->make(['period' => 'today']);
        $chartPeriod = $periods->make($filters, 'this_month');

        return view('dashboard.admin', [
            'today' => $financials->summary($today),
            'inventory' => $inventory->summary(),
            'stockWarnings' => $alerts->warnings(),
            'chartPeriod' => $chartPeriod,
            'charts' => $analytics->charts($chartPeriod),
            'filters' => $filters,
            'formatter' => $formatter,
            'recentSales' => Sale::query()->with(['cashier', 'reversal'])->latest('sold_at')->limit(5)->get(),
        ]);
    }
}
