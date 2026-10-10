<?php

namespace App\Http\Requests\Admin;

use App\Enums\PaymentMethod;
use App\Enums\SaleReversalStockAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleReversalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('reverse', $this->route('sale')) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'stock_action' => ['required', Rule::enum(SaleReversalStockAction::class)],
            'refund_method' => ['required', Rule::enum(PaymentMethod::class)],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'reversed_at' => ['required', 'date'],
        ];
    }
}
