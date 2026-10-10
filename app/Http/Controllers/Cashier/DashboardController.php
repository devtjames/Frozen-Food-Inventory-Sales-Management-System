<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\BusinessFormatter;
use App\Services\FinancialReportService;
use App\Services\ReportingPeriodFactory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, ReportingPeriodFactory $periods, FinancialReportService $financials, BusinessFormatter $formatter): View
    {
        $today = $periods->make(['period' => 'today']);
        $cashier = $request->user();
        $financialSummary = $financials->summary($today, $cashier->id);

        return view('dashboard.cashier', [
            'summary' => [
                'revenue' => $financialSummary['revenue'],
                'transactions' => $financialSummary['transactions'],
            ],
            'recentSales' => Sale::query()->with('reversal')->where('cashier_id', $cashier->id)->latest('sold_at')->limit(5)->get(),
            'formatter' => $formatter,
        ]);
    }
}
