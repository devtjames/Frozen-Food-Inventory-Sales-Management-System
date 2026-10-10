<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use App\Rules\UniqueCategoryName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCategoryRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = Str::squish((string) $this->input('name'));

        $this->merge([
            'name' => $name,
            'normalized_name' => Str::lower($name),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('create', Category::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', new UniqueCategoryName],
            'normalized_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
