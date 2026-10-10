<?php

namespace App\Services;

use App\Models\ExpenseCategory;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): ExpenseCategory
    {
        return DB::transaction(fn (): ExpenseCategory => ExpenseCategory::query()->create([
            'name' => $attributes['name'],
            'normalized_name' => $attributes['normalized_name'],
        ]));
    }

    /** @param array<string, mixed> $attributes */
    public function update(ExpenseCategory $category, array $attributes): ExpenseCategory
    {
        return DB::transaction(function () use ($category, $attributes): ExpenseCategory {
            $locked = ExpenseCategory::query()->lockForUpdate()->findOrFail($category->getKey());
            $locked->update([
                'name' => $attributes['name'],
                'normalized_name' => $attributes['normalized_name'],
            ]);

            return $locked->refresh();
        });
    }
}
