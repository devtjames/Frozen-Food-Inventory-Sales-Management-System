<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class SaleHistoryService
{
    /** @param array<string, mixed> $filters */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $query = Sale::query()
            ->with(['cashier', 'reversal.refund'])
            ->latest('sold_at');

        if ($user->isCashier()) {
            $query->where('cashier_id', $user->id);
        } elseif (! empty($filters['cashier'])) {
            $query->where('cashier_id', $filters['cashier']);
        }

        $query->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query->where('transaction_number', 'like', '%'.trim($search).'%'));
        $query->when($filters['payment_method'] ?? null, fn (Builder $query, string $method): Builder => $query->where('payment_method', $method));

        if (($filters['transaction_status'] ?? null) === 'completed') {
            $query->whereDoesntHave('reversal');
        } elseif (($filters['transaction_status'] ?? null) === 'reversed') {
            $query->whereHas('reversal');
        }

        if (($filters['payment_status'] ?? null) === 'paid') {
            $query->whereDoesntHave('refund');
        } elseif (($filters['payment_status'] ?? null) === 'refunded') {
            $query->whereHas('refund');
        }

        if (! empty($filters['date_from'])) {
            $query->where('sold_at', '>=', $this->dateBoundary((string) $filters['date_from'], false));
        }

        if (! empty($filters['date_to'])) {
            $query->where('sold_at', '<=', $this->dateBoundary((string) $filters['date_to'], true));
        }

        return $query->paginate(10)->withQueryString();
    }

    private function dateBoundary(string $date, bool $endOfDay): Carbon
    {
        $boundary = Carbon::parse($date, (string) config('app.business_timezone'));

        return ($endOfDay ? $boundary->endOfDay() : $boundary->startOfDay())->utc();
    }
}
