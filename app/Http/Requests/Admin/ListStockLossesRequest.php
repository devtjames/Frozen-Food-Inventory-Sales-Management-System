<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockLossCategory;
use App\Models\StockLoss;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStockLossesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'product' => $this->filled('product') ? $this->input('product') : null,
            'category' => $this->filled('category') ? $this->input('category') : null,
            'status' => $this->filled('status') ? $this->input('status') : null,
            'date_from' => $this->filled('date_from') ? $this->input('date_from') : null,
            'date_to' => $this->filled('date_to') ? $this->input('date_to') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockLoss::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'product' => ['nullable', 'integer', 'exists:products,id'],
            'category' => ['nullable', Rule::enum(StockLossCategory::class)],
            'status' => ['nullable', Rule::in(['active', 'reversed'])],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
        ];
    }
}
