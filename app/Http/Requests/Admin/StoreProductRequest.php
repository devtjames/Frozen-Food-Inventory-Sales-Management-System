<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Rules\UniqueProductName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class StoreProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = Str::squish((string) $this->input('name'));

        $this->merge([
            'name' => $name,
            'normalized_name' => Str::lower($name),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Product::class) ?? false;
    }

    public function rules(): array
    {
        $categoryId = (int) $this->input('category_id');

        return [
            'name' => ['required', 'string', 'max:150', new UniqueProductName($categoryId)],
            'normalized_name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'selling_price' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'low_stock_threshold' => ['required', 'decimal:0,3', 'min:0', 'max:999999999.999'],
            'is_active' => ['required', 'boolean'],
            'image' => $this->imageRules(),
        ];
    }

    /** @return array<int, mixed> */
    private function imageRules(): array
    {
        return [
            'nullable',
            File::image()
                ->types(['jpg', 'jpeg', 'png', 'webp'])
                ->max((int) config('products.image_max_kilobytes')),
            'dimensions:max_width=3000,max_height=3000',
        ];
    }
}
