<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockLossReversalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['reason' => trim((string) $this->input('reason'))]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('reverse', $this->route('loss')) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
            'reversed_at' => ['required', 'date'],
        ];
    }
}
