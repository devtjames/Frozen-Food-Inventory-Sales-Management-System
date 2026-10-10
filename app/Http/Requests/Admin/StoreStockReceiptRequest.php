<?php

namespace App\Http\Requests\Admin;

use App\Models\StockReceipt;
use Illuminate\Foundation\Http\FormRequest;

class StoreStockReceiptRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'supplier_name' => $this->filled('supplier_name') ? trim((string) $this->input('supplier_name')) : null,
            'reference_number' => $this->filled('reference_number') ? trim((string) $this->input('reference_number')) : null,
            'notes' => $this->filled('notes') ? trim((string) $this->input('notes')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', StockReceipt::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity_received' => ['required', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'purchase_cost_per_kg' => ['required', 'decimal:0,2', 'gt:0', 'max:9999999999.99'],
            'supplier_name' => ['nullable', 'string', 'max:255'],
            'received_at' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
