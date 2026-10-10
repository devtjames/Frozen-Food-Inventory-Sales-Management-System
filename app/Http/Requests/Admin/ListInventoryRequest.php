<?php

namespace App\Http\Requests\Admin;

use App\Models\StockReceipt;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListInventoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'category' => $this->filled('category') ? $this->input('category') : null,
            'stock_status' => $this->filled('stock_status') ? $this->input('stock_status') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockReceipt::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'stock_status' => ['nullable', Rule::in(['low', 'in_stock'])],
        ];
    }
}
