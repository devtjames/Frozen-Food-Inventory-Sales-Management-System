<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => Str::squish((string) $this->input('description')),
            'amount' => trim((string) $this->input('amount')),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
            'correction_reason' => trim((string) $this->input('correction_reason')),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('expense')) ?? false;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'expense_category_id' => ['required', 'integer', Rule::exists('expense_categories', 'id')],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'regex:/^\d{1,22}(?:\.\d{1,2})?$/', 'gt:0'],
            'expense_date' => ['required', 'date_format:Y-m-d'],
            'note' => ['nullable', 'string', 'max:2000'],
            'correction_reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
