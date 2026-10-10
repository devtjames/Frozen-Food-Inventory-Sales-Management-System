<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ExpenseReportingService
{
    public function __construct(private readonly DecimalMath $math) {}

    public function totalForRange(string $from, string $to, ?int $categoryId = null): string
    {
        $query = Expense::query()->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to);

        if ($categoryId !== null) {
            $query->where('expense_category_id', $categoryId);
        }

        return $this->math->normalize((string) $query->sum('amount'), 2);
    }

    /** @return Collection<string, string> */
    public function dailyTotals(string $from, string $to): Collection
    {
        return $this->groupedTotals($from, $to, fn (Expense $expense): string => $expense->expense_date->format('Y-m-d'));
    }

    /** @return Collection<string, string> */
    public function weeklyTotals(string $from, string $to): Collection
    {
        return $this->groupedTotals($from, $to, fn (Expense $expense): string => Carbon::parse($expense->expense_date)->startOfWeek()->format('Y-m-d'));
    }

    /** @return Collection<string, string> */
    public function monthlyTotals(string $from, string $to): Collection
    {
        return $this->groupedTotals($from, $to, fn (Expense $expense): string => $expense->expense_date->format('Y-m'));
    }

    /** @return Collection<int, array{category: string, total: string}> */
    public function categoryTotals(string $from, string $to): Collection
    {
        return Expense::query()->with('category')->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to)->get()
            ->groupBy('expense_category_id')
            ->map(fn (Collection $expenses): array => [
                'category' => $expenses->first()->category->name,
                'total' => $this->sum($expenses),
            ]);
    }

    /** @return Collection<string, string> */
    private function groupedTotals(string $from, string $to, callable $key): Collection
    {
        return Expense::query()->whereDate('expense_date', '>=', $from)->whereDate('expense_date', '<=', $to)->get()
            ->groupBy($key)
            ->map(fn (Collection $expenses): string => $this->sum($expenses));
    }

    private function sum(Collection $expenses): string
    {
        return $expenses->reduce(fn (string $total, Expense $expense): string => $this->math->add($total, $expense->amount, 2), '0.00');
    }
}
