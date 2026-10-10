<?php

namespace App\Services;

use App\Models\Sale;
use App\ValueObjects\ReportingPeriod;
use Illuminate\Support\Collection;

class SalesAnalyticsService
{
    public function __construct(
        private readonly FinancialReportService $financials,
        private readonly DecimalMath $math,
    ) {}

    /** @return array<string, array{labels: array<int, string>, values: array<int, string>}> */
    public function charts(ReportingPeriod $period): array
    {
        $sales = $this->financials->validSalesQuery($period)->with(['cashier', 'items'])->orderBy('sold_at')->get();

        return [
            'daily' => $this->salesGrouped($sales, fn (Sale $sale): string => $sale->sold_at->timezone(config('app.business_timezone'))->format('d M')),
            'weekly' => $this->salesGrouped($sales, fn (Sale $sale): string => 'Week of '.$sale->sold_at->timezone(config('app.business_timezone'))->startOfWeek()->format('d M')),
            'monthly' => $this->salesGrouped($sales, fn (Sale $sale): string => $sale->sold_at->timezone(config('app.business_timezone'))->format('M Y')),
            'products' => $this->itemsGrouped($sales, 'product_name'),
            'categories' => $this->itemsGrouped($sales, 'category_name'),
            'cashiers' => $this->salesGrouped($sales, fn (Sale $sale): string => $sale->cashier->name),
        ];
    }

    /** @param Collection<int, Sale> $sales
     * @return array{labels: array<int, string>, values: array<int, string>}
     */
    private function salesGrouped(Collection $sales, callable $key): array
    {
        $totals = $sales->groupBy($key)->map(fn (Collection $group): string => $group->reduce(
            fn (string $sum, Sale $sale): string => $this->math->add($sum, $sale->total_amount, 2),
            '0.00',
        ));

        return ['labels' => $totals->keys()->values()->all(), 'values' => $totals->values()->all()];
    }

    /** @param Collection<int, Sale> $sales
     * @return array{labels: array<int, string>, values: array<int, string>}
     */
    private function itemsGrouped(Collection $sales, string $field): array
    {
        $totals = $sales->flatMap->items->groupBy(fn ($item): string => $item->{$field} ?: 'Uncategorised')
            ->map(fn (Collection $items): string => $items->reduce(
                fn (string $sum, $item): string => $this->math->add($sum, $item->item_amount, 2),
                '0.00',
            ))->sortDesc()->take(10);

        return ['labels' => $totals->keys()->values()->all(), 'values' => $totals->values()->all()];
    }
}
