<?php

namespace App\Http\Requests\Admin;

use App\Models\Product;
use App\Rules\UniqueProductName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\File;

class UpdateProductRequest extends FormRequest
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
        $product = $this->route('product');

        return $product instanceof Product && ($this->user()?->can('update', $product) ?? false);
    }

    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');
        $categoryId = (int) $this->input('category_id');

        return [
            'name' => ['required', 'string', 'max:150', new UniqueProductName($categoryId, $product->getKey())],
            'normalized_name' => ['required', 'string', 'max:150'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'description' => ['nullable', 'string', 'max:2000'],
            'selling_price' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'low_stock_threshold' => ['required', 'decimal:0,3', 'min:0', 'max:999999999.999'],
            'remove_image' => ['sometimes', 'boolean'],
            'image' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max((int) config('products.image_max_kilobytes')),
                'dimensions:max_width=3000,max_height=3000',
            ],
        ];
    }
}
