<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class UniqueProductName implements ValidationRule
{
    public function __construct(
        private readonly int $categoryId,
        private readonly ?int $ignoreProductId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = Product::query()
            ->where('category_id', $this->categoryId)
            ->where('normalized_name', Str::lower(Str::squish((string) $value)))
            ->when($this->ignoreProductId, fn ($query) => $query->whereKeyNot($this->ignoreProductId))
            ->exists();

        if ($exists) {
            $fail('A product with this name already exists in the selected category.');
        }
    }
}
