<?php

namespace App\Services;

use App\Enums\ReportType;
use App\Models\Expense;
use App\Models\Sale;
use App\Models\SaleReversalItem;
use App\Models\StockLoss;
use App\Models\StockReceipt;
use App\ValueObjects\ReportingPeriod;
use App\ValueObjects\ReportResult;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportDataService
{
    public function __construct(
        private readonly FinancialReportService $financials,
        private readonly InventoryReportService $inventory,
        private readonly DecimalMath $math,
    ) {}

    /** @param array<string, mixed> $filters */
    public function generate(ReportType $type, ReportingPeriod $period, array $filters): ReportResult
    {
        return match ($type) {
            ReportType::Sales => $this->sales($period, $filters),
            ReportType::ProductSales => $this->productSales($period, $filters),
            ReportType::CategorySales => $this->categorySales($period, $filters),
            ReportType::CashierSales => $this->cashierSales($period, $filters),
            ReportType::Inventory => $this->inventory($filters),
            ReportType::StockReceiving => $this->receiving($period, $filters),
            ReportType::StockLoss => $this->stockLosses($period, $filters),
            ReportType::Expense => $this->expenses($period, $filters),
            ReportType::Profit => $this->profit($period, $filters),
        };
    }

    /** @param array<string, mixed> $filters */
    private function sales(ReportingPeriod $period, array $filters): ReportResult
    {
        $sales = Sale::query()->with(['cashier', 'reversal.refund'])
            ->whereBetween('sold_at', [$period->fromUtc(), $period->toUtc()])
            ->when($filters['cashier'] ?? null, fn (Builder $query, int $id): Builder => $query->where('cashier_id', $id))
            ->when($filters['product'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('items', fn (Builder $query): Builder => $query->where('product_id', $id)))
            ->when($filters['category'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('items', fn (Builder $query): Builder => $query->where('category_id', $id)))
            ->latest('sold_at')->get();
        $validSales = $sales->reject->isReversed();

        return new ReportResult(ReportType::Sales, [
            'transaction' => ['label' => 'Transaction', 'type' => 'text'],
            'date' => ['label' => 'Date', 'type' => 'datetime'],
            'cashier' => ['label' => 'Cashier', 'type' => 'text'],
            'payment_method' => ['label' => 'Payment method', 'type' => 'text'],
            'amount' => ['label' => 'Amount', 'type' => 'money'],
            'payment_status' => ['label' => 'Payment status', 'type' => 'text'],
            'status' => ['label' => 'Transaction status', 'type' => 'text'],
        ], $sales->map(fn (Sale $sale): array => [
            'transaction' => $sale->transaction_number,
            'date' => $sale->sold_at,
            'cashier' => $sale->cashier->name,
            'payment_method' => $sale->payment_method->label(),
            'amount' => $sale->total_amount,
            'payment_status' => $sale->effectivePaymentStatus(),
            'status' => $sale->effectiveTransactionStatus(),
        ]), [
            ['label' => 'Valid revenue', 'value' => $this->sum($validSales, 'total_amount', 2), 'type' => 'money'],
            ['label' => 'Valid transactions', 'value' => $validSales->count(), 'type' => 'integer'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function productSales(ReportingPeriod $period, array $filters): ReportResult
    {
        $items = $this->validItems($period, $filters);
        $rows = $items->groupBy('product_id')->map(function (Collection $items): array {
            $revenue = $this->sum($items, 'item_amount', 2);
            $cogs = $this->sum($items, 'purchase_cost_total', 2);

            return [
                'product' => $items->first()->product_name,
                'quantity' => $this->sum($items, 'quantity', 3),
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $this->math->subtract($revenue, $cogs, 2),
            ];
        })->sortByDesc('revenue')->values();

        return new ReportResult(ReportType::ProductSales, $this->salesDimensionColumns('Product'), $rows, $this->salesTotals($rows));
    }

    /** @param array<string, mixed> $filters */
    private function categorySales(ReportingPeriod $period, array $filters): ReportResult
    {
        $items = $this->validItems($period, $filters);
        $rows = $items->groupBy(fn ($item): string => (string) ($item->category_id ?: $item->category_name ?: 'uncategorised'))->map(function (Collection $items): array {
            $revenue = $this->sum($items, 'item_amount', 2);
            $cogs = $this->sum($items, 'purchase_cost_total', 2);

            return [
                'category' => $items->first()->category_name ?: 'Uncategorised',
                'quantity' => $this->sum($items, 'quantity', 3),
                'revenue' => $revenue,
                'cogs' => $cogs,
                'gross_profit' => $this->math->subtract($revenue, $cogs, 2),
            ];
        })->sortByDesc('revenue')->values();
        $columns = [
            'category' => ['label' => 'Category', 'type' => 'text'],
            'quantity' => ['label' => 'Quantity sold', 'type' => 'kg'],
            'revenue' => ['label' => 'Revenue', 'type' => 'money'],
            'cogs' => ['label' => 'FIFO COGS', 'type' => 'money'],
            'gross_profit' => ['label' => 'Gross profit', 'type' => 'money'],
        ];

        return new ReportResult(ReportType::CategorySales, $columns, $rows, $this->salesTotals($rows));
    }

    /** @param array<string, mixed> $filters */
    private function cashierSales(ReportingPeriod $period, array $filters): ReportResult
    {
        $sales = $this->financials->validSalesQuery($period, isset($filters['cashier']) ? (int) $filters['cashier'] : null)->with('cashier')->get();
        $rows = $sales->groupBy('cashier_id')->map(fn (Collection $sales): array => [
            'cashier' => $sales->first()->cashier->name,
            'transactions' => $sales->count(),
            'revenue' => $this->sum($sales, 'total_amount', 2),
        ])->sortByDesc('revenue')->values();

        return new ReportResult(ReportType::CashierSales, [
            'cashier' => ['label' => 'Cashier', 'type' => 'text'],
            'transactions' => ['label' => 'Transactions', 'type' => 'integer'],
            'revenue' => ['label' => 'Revenue', 'type' => 'money'],
        ], $rows, [
            ['label' => 'Revenue', 'value' => $this->sum($rows, 'revenue', 2), 'type' => 'money'],
            ['label' => 'Transactions', 'value' => (int) $rows->sum('transactions'), 'type' => 'integer'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function inventory(array $filters): ReportResult
    {
        $rows = $this->inventory->products(
            isset($filters['product']) ? (int) $filters['product'] : null,
            isset($filters['category']) ? (int) $filters['category'] : null,
        )->map(fn ($product): array => $this->inventory->row($product));

        return new ReportResult(ReportType::Inventory, [
            'product' => ['label' => 'Product', 'type' => 'text'],
            'category' => ['label' => 'Category', 'type' => 'text'],
            'available_quantity' => ['label' => 'Available', 'type' => 'kg'],
            'received_quantity' => ['label' => 'Total received', 'type' => 'kg'],
            'sold_quantity' => ['label' => 'Valid quantity sold', 'type' => 'kg'],
            'damaged_quantity' => ['label' => 'Damaged / spoiled', 'type' => 'kg'],
            'inventory_value' => ['label' => 'Inventory value', 'type' => 'money'],
            'stock_status' => ['label' => 'Stock status', 'type' => 'text'],
            'product_status' => ['label' => 'Product status', 'type' => 'text'],
        ], $rows, [
            ['label' => 'Available stock', 'value' => $this->sum($rows, 'available_quantity', 3), 'type' => 'kg'],
            ['label' => 'Stock value', 'value' => $this->sum($rows, 'inventory_value', 2), 'type' => 'money'],
            ['label' => 'Low-stock products', 'value' => $rows->where('product_status', 'Active')->filter(fn (array $row): bool => in_array($row['stock_status'], ['Low stock', 'Out of stock'], true))->count(), 'type' => 'integer'],
            ['label' => 'Out-of-stock products', 'value' => $rows->where('product_status', 'Active')->where('stock_status', 'Out of stock')->count(), 'type' => 'integer'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function receiving(ReportingPeriod $period, array $filters): ReportResult
    {
        $receipts = StockReceipt::query()->with(['product.category', 'recorder'])
            ->whereBetween('received_at', [$period->fromUtc(), $period->toUtc()])
            ->when($filters['product'] ?? null, fn (Builder $query, int $id): Builder => $query->where('product_id', $id))
            ->when($filters['category'] ?? null, fn (Builder $query, int $id): Builder => $query->whereHas('product', fn (Builder $query): Builder => $query->where('category_id', $id)))
            ->latest('received_at')->get();

        return new ReportResult(ReportType::StockReceiving, [
            'date' => ['label' => 'Received date', 'type' => 'datetime'],
            'reference' => ['label' => 'Reference', 'type' => 'text'],
            'product' => ['label' => 'Product', 'type' => 'text'],
            'quantity' => ['label' => 'Quantity', 'type' => 'kg'],
            'cost_per_kg' => ['label' => 'Cost / kg', 'type' => 'money'],
            'total_cost' => ['label' => 'Total cost', 'type' => 'money'],
            'supplier' => ['label' => 'Supplier', 'type' => 'text'],
            'recorded_by' => ['label' => 'Recorded by', 'type' => 'text'],
        ], $receipts->map(fn (StockReceipt $receipt): array => [
            'date' => $receipt->received_at,
            'reference' => $receipt->reference_number ?: '—',
            'product' => $receipt->product->name,
            'quantity' => $receipt->quantity_received,
            'cost_per_kg' => $receipt->purchase_cost_per_kg,
            'total_cost' => $receipt->total_purchase_cost,
            'supplier' => $receipt->supplier_name ?: '—',
            'recorded_by' => $receipt->recorder->name,
        ]), [
            ['label' => 'Quantity received', 'value' => $this->sum($receipts, 'quantity_received', 3), 'type' => 'kg'],
            ['label' => 'Purchase cost', 'value' => $this->sum($receipts, 'total_purchase_cost', 2), 'type' => 'money'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function stockLosses(ReportingPeriod $period, array $filters): ReportResult
    {
        $losses = StockLoss::query()->with(['product', 'recorder', 'reversal'])
            ->whereBetween('lost_at', [$period->fromUtc(), $period->toUtc()])
            ->when($filters['product'] ?? null, fn (Builder $query, int $id): Builder => $query->where('product_id', $id))
            ->when($filters['loss_category'] ?? null, fn (Builder $query, string $category): Builder => $query->where('category', $category))
            ->latest('lost_at')->get();
        $recognized = $losses->reject->isReversed();

        return new ReportResult(ReportType::StockLoss, [
            'date' => ['label' => 'Loss date', 'type' => 'datetime'],
            'product' => ['label' => 'Product', 'type' => 'text'],
            'category' => ['label' => 'Loss category', 'type' => 'text'],
            'quantity' => ['label' => 'Quantity', 'type' => 'kg'],
            'financial_value' => ['label' => 'Financial value', 'type' => 'money'],
            'reason' => ['label' => 'Reason', 'type' => 'text'],
            'recorded_by' => ['label' => 'Recorded by', 'type' => 'text'],
            'status' => ['label' => 'Status', 'type' => 'text'],
        ], $losses->map(fn (StockLoss $loss): array => [
            'date' => $loss->lost_at,
            'product' => $loss->product->name,
            'category' => $loss->category->label(),
            'quantity' => $loss->quantity_lost,
            'financial_value' => $loss->financial_value,
            'reason' => $loss->reason,
            'recorded_by' => $loss->recorder->name,
            'status' => $loss->isReversed() ? 'Reversed' : 'Recognized',
        ]), [
            ['label' => 'Recognized quantity', 'value' => $this->sum($recognized, 'quantity_lost', 3), 'type' => 'kg'],
            ['label' => 'Recognized loss value', 'value' => $this->sum($recognized, 'financial_value', 2), 'type' => 'money'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function expenses(ReportingPeriod $period, array $filters): ReportResult
    {
        $expenses = Expense::query()->with(['category', 'recorder'])
            ->whereDate('expense_date', '>=', $period->fromDate())->whereDate('expense_date', '<=', $period->toDate())
            ->when($filters['expense_category'] ?? null, fn (Builder $query, int $id): Builder => $query->where('expense_category_id', $id))
            ->latest('expense_date')->latest('id')->get();

        return new ReportResult(ReportType::Expense, [
            'date' => ['label' => 'Expense date', 'type' => 'date'],
            'category' => ['label' => 'Category', 'type' => 'text'],
            'description' => ['label' => 'Description', 'type' => 'text'],
            'amount' => ['label' => 'Amount', 'type' => 'money'],
            'recorded_by' => ['label' => 'Recorded by', 'type' => 'text'],
        ], $expenses->map(fn (Expense $expense): array => [
            'date' => $expense->expense_date,
            'category' => $expense->category->name,
            'description' => $expense->description,
            'amount' => $expense->amount,
            'recorded_by' => $expense->recorder->name,
        ]), [['label' => 'Operating expenses', 'value' => $this->sum($expenses, 'amount', 2), 'type' => 'money']]);
    }

    /** @param array<string, mixed> $filters */
    private function profit(ReportingPeriod $period, array $filters): ReportResult
    {
        $cashierId = isset($filters['cashier']) ? (int) $filters['cashier'] : null;
        $sales = $this->financials->validSalesQuery($period, $cashierId)->with('items')->get();
        $expenses = $cashierId === null ? Expense::query()->whereDate('expense_date', '>=', $period->fromDate())->whereDate('expense_date', '<=', $period->toDate())->get() : collect();
        $losses = $cashierId === null ? StockLoss::query()->active()->whereBetween('lost_at', [$period->fromUtc(), $period->toUtc()])->get() : collect();
        $reversalLosses = $cashierId === null ? SaleReversalItem::query()->with('reversal')->where('stock_restored', false)
            ->whereHas('reversal', fn (Builder $query): Builder => $query->whereBetween('reversed_at', [$period->fromUtc(), $period->toUtc()]))->get() : collect();
        $timezone = (string) config('app.business_timezone');
        $dates = collect()
            ->merge($sales->map(fn (Sale $sale): string => $sale->sold_at->timezone($timezone)->format('Y-m-d')))
            ->merge($expenses->pluck('expense_date')->map->format('Y-m-d'))
            ->merge($losses->map(fn (StockLoss $loss): string => $loss->lost_at->timezone($timezone)->format('Y-m-d')))
            ->merge($reversalLosses->map(fn (SaleReversalItem $item): string => $item->reversal->reversed_at->timezone($timezone)->format('Y-m-d')))
            ->unique()->sort()->values();
        $rows = $dates->map(function (string $date) use ($sales, $expenses, $losses, $reversalLosses, $timezone): array {
            $daySales = $sales->filter(fn (Sale $sale): bool => $sale->sold_at->timezone($timezone)->format('Y-m-d') === $date);
            $revenue = $this->sum($daySales, 'total_amount', 2);
            $cogs = $daySales->flatMap->items->reduce(fn (string $sum, $item): string => $this->math->add($sum, $item->purchase_cost_total, 2), '0.00');
            $operatingExpenses = $this->sum($expenses->filter(fn (Expense $expense): bool => $expense->expense_date->format('Y-m-d') === $date), 'amount', 2);
            $inventoryLosses = $this->sum($losses->filter(fn (StockLoss $loss): bool => $loss->lost_at->timezone($timezone)->format('Y-m-d') === $date), 'financial_value', 2);
            $inventoryLosses = $this->math->add($inventoryLosses, $this->sum($reversalLosses->filter(fn (SaleReversalItem $item): bool => $item->reversal->reversed_at->timezone($timezone)->format('Y-m-d') === $date), 'purchase_cost_total', 2), 2);
            $gross = $this->math->subtract($revenue, $cogs, 2);

            return ['date' => $date, 'revenue' => $revenue, 'cogs' => $cogs, 'gross_profit' => $gross, 'expenses' => $operatingExpenses, 'inventory_losses' => $inventoryLosses, 'net_profit' => $this->math->subtract($this->math->subtract($gross, $operatingExpenses, 2), $inventoryLosses, 2)];
        });
        $summary = $this->financials->summary($period, $cashierId);

        return new ReportResult(ReportType::Profit, [
            'date' => ['label' => 'Date', 'type' => 'date'],
            'revenue' => ['label' => 'Revenue', 'type' => 'money'],
            'cogs' => ['label' => 'FIFO COGS', 'type' => 'money'],
            'gross_profit' => ['label' => 'Gross profit', 'type' => 'money'],
            'expenses' => ['label' => 'Operating expenses', 'type' => 'money'],
            'inventory_losses' => ['label' => 'Inventory losses', 'type' => 'money'],
            'net_profit' => ['label' => 'Estimated net profit', 'type' => 'money'],
        ], $rows, [
            ['label' => 'Revenue', 'value' => $summary['revenue'], 'type' => 'money'],
            ['label' => 'FIFO COGS', 'value' => $summary['cogs'], 'type' => 'money'],
            ['label' => 'Gross profit', 'value' => $summary['gross_profit'], 'type' => 'money'],
            ['label' => 'Operating expenses', 'value' => $summary['expenses'], 'type' => 'money'],
            ['label' => 'Inventory losses', 'value' => $summary['inventory_losses'], 'type' => 'money'],
            ['label' => 'Estimated net profit', 'value' => $summary['net_profit'], 'type' => 'money'],
        ]);
    }

    /** @param array<string, mixed> $filters */
    private function validItems(ReportingPeriod $period, array $filters): Collection
    {
        $sales = $this->financials->validSalesQuery($period, isset($filters['cashier']) ? (int) $filters['cashier'] : null)->with('items')->get();

        return $sales->flatMap->items
            ->when($filters['product'] ?? null, fn (Collection $items, mixed $id): Collection => $items->where('product_id', (int) $id))
            ->when($filters['category'] ?? null, fn (Collection $items, mixed $id): Collection => $items->where('category_id', (int) $id));
    }

    /** @return array<string, array{label: string, type: string}> */
    private function salesDimensionColumns(string $dimension): array
    {
        return [
            'product' => ['label' => $dimension, 'type' => 'text'],
            'quantity' => ['label' => 'Quantity sold', 'type' => 'kg'],
            'revenue' => ['label' => 'Revenue', 'type' => 'money'],
            'cogs' => ['label' => 'FIFO COGS', 'type' => 'money'],
            'gross_profit' => ['label' => 'Gross profit', 'type' => 'money'],
        ];
    }

    /** @param Collection<int, array<string, mixed>> $rows
     * @return array<int, array{label: string, value: string|int, type: string}>
     */
    private function salesTotals(Collection $rows): array
    {
        return [
            ['label' => 'Quantity sold', 'value' => $this->sum($rows, 'quantity', 3), 'type' => 'kg'],
            ['label' => 'Revenue', 'value' => $this->sum($rows, 'revenue', 2), 'type' => 'money'],
            ['label' => 'FIFO COGS', 'value' => $this->sum($rows, 'cogs', 2), 'type' => 'money'],
            ['label' => 'Gross profit', 'value' => $this->sum($rows, 'gross_profit', 2), 'type' => 'money'],
        ];
    }

    private function sum(Collection $rows, string $field, int $scale): string
    {
        return $rows->reduce(fn (string $sum, mixed $row): string => $this->math->add($sum, data_get($row, $field), $scale), $scale === 3 ? '0.000' : '0.00');
    }
}
