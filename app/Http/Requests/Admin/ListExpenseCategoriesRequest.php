<?php

namespace App\Http\Requests\Admin;

use App\Models\ExpenseCategory;
use Illuminate\Foundation\Http\FormRequest;

class ListExpenseCategoriesRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['search' => trim((string) $this->input('search')) ?: null]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ExpenseCategory::class) ?? false;
    }

    public function rules(): array
    {
        return ['search' => ['nullable', 'string', 'max:100']];
    }
}
