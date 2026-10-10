<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CategoryService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Category
    {
        return DB::transaction(fn (): Category => Category::query()->create(
            Arr::only($attributes, ['name', 'normalized_name', 'description', 'is_active']),
        ));
    }

    /** @param array<string, mixed> $attributes */
    public function update(Category $category, array $attributes): Category
    {
        return DB::transaction(function () use ($category, $attributes): Category {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            $lockedCategory->update(Arr::only($attributes, ['name', 'normalized_name', 'description']));

            return $lockedCategory->refresh();
        });
    }

    public function setActive(Category $category, bool $isActive): Category
    {
        return DB::transaction(function () use ($category, $isActive): Category {
            $lockedCategory = Category::query()->lockForUpdate()->findOrFail($category->getKey());
            $lockedCategory->update(['is_active' => $isActive]);

            return $lockedCategory->refresh();
        });
    }
}
