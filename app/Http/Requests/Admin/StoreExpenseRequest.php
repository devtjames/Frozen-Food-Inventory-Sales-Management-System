<?php

namespace App\Http\Requests\Admin;

use App\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'description' => Str::squish((string) $this->input('description')),
            'amount' => trim((string) $this->input('amount')),
            'note' => $this->filled('note') ? trim((string) $this->input('note')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Expense::class) ?? false;
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
        ];
    }
}
