<?php

namespace App\Http\Requests\Admin;

use App\Models\StockReceipt;
use Illuminate\Foundation\Http\FormRequest;

class ListStockReceiptsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'search' => $this->filled('search') ? trim((string) $this->input('search')) : null,
            'product' => $this->filled('product') ? $this->input('product') : null,
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
            'product' => ['nullable', 'integer', 'exists:products,id'],
        ];
    }
}
