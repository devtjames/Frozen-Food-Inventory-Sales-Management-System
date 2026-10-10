<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ExpenseHistoryService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @param array<string, mixed> $filters
     * @return array{expenses: LengthAwarePaginator, total: string}
     */
    public function filtered(array $filters): array
    {
        $query = Expense::query()->with(['category', 'recorder'])->latest('expense_date')->latest('id');

        $query->when($filters['search'] ?? null, function (Builder $query, string $search): void {
            $query->where(fn (Builder $query): Builder => $query->where('description', 'like', '%'.$search.'%')->orWhere('note', 'like', '%'.$search.'%'));
        });
        $query->when($filters['category'] ?? null, fn (Builder $query, mixed $category): Builder => $query->where('expense_category_id', $category));
        $query->when($filters['date_from'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn (Builder $query, string $date): Builder => $query->whereDate('expense_date', '<=', $date));

        $total = $this->math->normalize((string) (clone $query)->reorder()->sum('amount'), 2);

        return [
            'expenses' => $query->paginate(10)->withQueryString(),
            'total' => $total,
        ];
    }
}
