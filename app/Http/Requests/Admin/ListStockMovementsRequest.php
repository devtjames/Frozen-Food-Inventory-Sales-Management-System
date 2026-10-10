<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListStockMovementsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'product' => $this->filled('product') ? $this->input('product') : null,
            'type' => $this->filled('type') ? $this->input('type') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', StockMovement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'product' => ['nullable', 'integer', 'exists:products,id'],
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
        ];
    }
}
