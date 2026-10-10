<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SaleReversalItem;
use App\Models\StockLoss;
use App\ValueObjects\ReportingPeriod;
use Illuminate\Database\Eloquent\Builder;

class FinancialReportService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @return array{revenue: string, transactions: int, cogs: string, gross_profit: string, expenses: string, inventory_losses: string, net_profit: string} */
    public function summary(ReportingPeriod $period, ?int $cashierId = null): array
    {
        $sales = $this->validSalesQuery($period, $cashierId);
        $revenue = $this->decimalSum(clone $sales, 'total_amount');
        $transactions = (clone $sales)->count();
        $cogs = $this->decimalSum(SaleItem::query()->whereHas('sale', function (Builder $query) use ($period, $cashierId): void {
            $this->constrainValidSales($query, $period, $cashierId);
        }), 'purchase_cost_total');
        $expenses = $cashierId === null
            ? $this->decimalSum(Expense::query()->whereDate('expense_date', '>=', $period->fromDate())->whereDate('expense_date', '<=', $period->toDate()), 'amount')
            : '0.00';
        $inventoryLosses = $cashierId === null ? $this->recognizedLosses($period) : '0.00';
        $grossProfit = $this->math->subtract($revenue, $cogs, 2);
        $netProfit = $this->math->subtract($this->math->subtract($grossProfit, $expenses, 2), $inventoryLosses, 2);

        return compact('revenue', 'transactions', 'cogs', 'expenses') + [
            'gross_profit' => $grossProfit,
            'inventory_losses' => $inventoryLosses,
            'net_profit' => $netProfit,
        ];
    }

    public function validSalesQuery(ReportingPeriod $period, ?int $cashierId = null): Builder
    {
        $query = Sale::query();
        $this->constrainValidSales($query, $period, $cashierId);

        return $query;
    }

    public function constrainValidSales(Builder $query, ReportingPeriod $period, ?int $cashierId = null): void
    {
        $query->whereBetween('sold_at', [$period->fromUtc(), $period->toUtc()])->whereDoesntHave('reversal');

        if ($cashierId !== null) {
            $query->where('cashier_id', $cashierId);
        }
    }

    public function recognizedLosses(ReportingPeriod $period): string
    {
        $stockLosses = $this->decimalSum(
            StockLoss::query()->active()->whereBetween('lost_at', [$period->fromUtc(), $period->toUtc()]),
            'financial_value',
        );
        $nonRestockedReversals = $this->decimalSum(
            SaleReversalItem::query()->where('stock_restored', false)
                ->whereHas('reversal', fn (Builder $query): Builder => $query->whereBetween('reversed_at', [$period->fromUtc(), $period->toUtc()])),
            'purchase_cost_total',
        );

        return $this->math->add($stockLosses, $nonRestockedReversals, 2);
    }

    private function decimalSum(Builder $query, string $column): string
    {
        return $this->math->normalize((string) $query->sum($column), 2);
    }
}
