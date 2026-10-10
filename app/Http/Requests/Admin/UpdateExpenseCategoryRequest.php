<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = Str::squish((string) $this->input('name'));
        $this->merge(['name' => $name, 'normalized_name' => Str::lower($name)]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('expense_category')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'normalized_name' => ['required', 'string', 'max:100', Rule::unique('expense_categories', 'normalized_name')->ignore($this->route('expense_category'))],
        ];
    }
}
