<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\ExpenseRevision;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

class ExpenseService
{
    public function __construct(private readonly DecimalMath $math) {}

    /** @param array<string, mixed> $attributes */
    public function create(array $attributes, User $administrator): Expense
    {
        $this->assertAdministrator($administrator);
        $key = (string) $attributes['idempotency_key'];

        if ($existing = Expense::query()->where('idempotency_key', $key)->first()) {
            return $existing->load(['category', 'recorder']);
        }

        try {
            return DB::transaction(function () use ($attributes, $administrator, $key): Expense {
                if ($existing = Expense::query()->where('idempotency_key', $key)->lockForUpdate()->first()) {
                    return $existing->load(['category', 'recorder']);
                }

                ExpenseCategory::query()->lockForUpdate()->findOrFail($attributes['expense_category_id']);
                $amount = $this->positiveAmount((string) $attributes['amount']);

                return Expense::query()->create([
                    'idempotency_key' => $key,
                    'expense_category_id' => $attributes['expense_category_id'],
                    'description' => $attributes['description'],
                    'amount' => $amount,
                    'expense_date' => $attributes['expense_date'],
                    'note' => $attributes['note'] ?? null,
                    'recorded_by' => $administrator->id,
                ])->load(['category', 'recorder']);
            });
        } catch (QueryException $exception) {
            if ($existing = Expense::query()->where('idempotency_key', $key)->first()) {
                return $existing->load(['category', 'recorder']);
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $attributes */
    public function update(Expense $expense, array $attributes, User $administrator): Expense
    {
        $this->assertAdministrator($administrator);
        $key = (string) $attributes['idempotency_key'];

        if ($revision = ExpenseRevision::query()->where('idempotency_key', $key)->first()) {
            return $this->matchingRevision($revision, $expense);
        }

        try {
            return DB::transaction(function () use ($expense, $attributes, $administrator, $key): Expense {
                $locked = Expense::query()->with('category')->lockForUpdate()->findOrFail($expense->getKey());

                if ($revision = ExpenseRevision::query()->where('idempotency_key', $key)->lockForUpdate()->first()) {
                    return $this->matchingRevision($revision, $locked);
                }

                $category = ExpenseCategory::query()->lockForUpdate()->findOrFail($attributes['expense_category_id']);
                $updated = [
                    'expense_category_id' => (int) $category->id,
                    'description' => (string) $attributes['description'],
                    'amount' => $this->positiveAmount((string) $attributes['amount']),
                    'expense_date' => (string) $attributes['expense_date'],
                    'note' => $attributes['note'] ?? null,
                ];
                $current = [
                    'expense_category_id' => (int) $locked->expense_category_id,
                    'description' => $locked->description,
                    'amount' => $locked->amount,
                    'expense_date' => $locked->expense_date->format('Y-m-d'),
                    'note' => $locked->note,
                ];
                $changed = array_keys(array_filter($updated, fn (mixed $value, string $field): bool => $value !== $current[$field], ARRAY_FILTER_USE_BOTH));

                if ($changed === []) {
                    return $locked->load(['category', 'recorder', 'revisions.editor']);
                }

                $previousValues = $this->auditValues($current, $changed, $locked->category->name);
                $updatedValues = $this->auditValues($updated, $changed, $category->name);
                $locked->update($updated);
                $locked->revisions()->create([
                    'idempotency_key' => $key,
                    'previous_values' => $previousValues,
                    'updated_values' => $updatedValues,
                    'reason' => $attributes['correction_reason'],
                    'changed_at' => now()->utc(),
                    'changed_by' => $administrator->id,
                ]);

                return $locked->fresh()->load(['category', 'recorder', 'revisions.editor']);
            });
        } catch (QueryException $exception) {
            if ($revision = ExpenseRevision::query()->where('idempotency_key', $key)->first()) {
                return $this->matchingRevision($revision, $expense);
            }

            throw $exception;
        }
    }

    /** @param array<string, mixed> $values
     * @param  list<string>  $changed
     * @return array<string, mixed>
     */
    private function auditValues(array $values, array $changed, string $categoryName): array
    {
        $audit = [];

        foreach ($changed as $field) {
            if ($field === 'expense_category_id') {
                $audit['category'] = $categoryName;
            } else {
                $audit[$field] = $values[$field];
            }
        }

        return $audit;
    }

    private function positiveAmount(string $amount): string
    {
        $amount = $this->math->normalize($amount, 2);

        if ($this->math->compare($amount, '0.00', 2) <= 0) {
            throw new InvalidArgumentException('The expense amount must be greater than zero.');
        }

        return $amount;
    }

    private function assertAdministrator(User $user): void
    {
        if (! $user->isAdministrator() || ! $user->is_active) {
            throw new LogicException('Only an active administrator can manage expenses.');
        }
    }

    private function matchingRevision(ExpenseRevision $revision, Expense $expense): Expense
    {
        if ($revision->expense_id !== $expense->getKey()) {
            throw new InvalidArgumentException('The submission key belongs to another expense correction.');
        }

        return $revision->expense->load(['category', 'recorder', 'revisions.editor']);
    }
}
