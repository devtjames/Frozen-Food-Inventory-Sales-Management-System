<?php

namespace App\Http\Requests;

use App\Enums\PaymentMethod;
use App\Models\Sale;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListSalesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['search' => trim((string) $this->input('search')) ?: null]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Sale::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'cashier' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'cashier')],
            'payment_method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'payment_status' => ['nullable', Rule::in(['paid', 'refunded'])],
            'transaction_status' => ['nullable', Rule::in(['completed', 'reversed'])],
        ];
    }
}
