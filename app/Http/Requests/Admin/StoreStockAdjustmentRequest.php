<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockAdjustmentType;
use App\Models\StockAdjustment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason' => trim((string) $this->input('reason')),
            'reference_number' => $this->filled('reference_number') ? trim((string) $this->input('reference_number')) : null,
            'cost_basis_per_kg' => $this->filled('cost_basis_per_kg') ? $this->input('cost_basis_per_kg') : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', StockAdjustment::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', Rule::enum(StockAdjustmentType::class)],
            'quantity' => ['required', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'cost_basis_per_kg' => [
                Rule::requiredIf($this->input('type') === StockAdjustmentType::Positive->value),
                'nullable',
                'decimal:0,2',
                'gt:0',
                'max:9999999999.99',
                Rule::prohibitedIf($this->input('type') === StockAdjustmentType::Negative->value),
            ],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'adjusted_at' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ];
    }
}
