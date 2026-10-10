<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueCategoryName implements ValidationRule
{
    public function __construct(private readonly ?int $ignoreCategoryId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Category::query()
            ->where('normalized_name', Str::lower(Str::squish((string) $value)))
            ->when($this->ignoreCategoryId, fn ($query) => $query->whereKeyNot($this->ignoreCategoryId))
            ->exists();

        if ($exists) {
            $fail('A category with this name already exists.');
        }
    }
}
