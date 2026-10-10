<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = Str::squish((string) $this->input('name'));
        $this->merge(['name' => $name, 'normalized_name' => Str::lower($name)]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', ExpenseCategory::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'normalized_name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'normalized_name')],
        ];
    }
}
