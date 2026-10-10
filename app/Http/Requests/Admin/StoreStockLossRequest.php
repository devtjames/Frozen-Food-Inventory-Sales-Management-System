<?php

namespace App\Http\Requests\Admin;

use App\Enums\StockLossCategory;
use App\Models\StockLoss;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockLossRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'stock_batch_id' => $this->filled('stock_batch_id') ? $this->input('stock_batch_id') : null,
            'reason' => trim((string) $this->input('reason')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', StockLoss::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'stock_batch_id' => [
                'nullable',
                'integer',
                Rule::exists('stock_batches', 'id')->where(fn (Builder $query) => $query
                    ->where('product_id', $this->input('product_id'))
                    ->where('remaining_quantity', '>', 0)),
            ],
            'category' => ['required', Rule::enum(StockLossCategory::class)],
            'quantity_lost' => ['required', 'decimal:0,3', 'gt:0', 'max:999999999.999'],
            'reason' => ['required', 'string', 'min:5', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'lost_at' => ['required', 'date'],
        ];
    }
}
