<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdministrator() ?? false;
    }

    public function rules(): array
    {
        return [
            'period' => ['nullable', Rule::in(['today', 'yesterday', 'this_week', 'this_month', 'custom'])],
            'date_from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'product' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'category' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'cashier' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'cashier')],
            'expense_category' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')],
            'loss_category' => ['nullable', Rule::in(['spoiled', 'damaged', 'freezer_failure', 'missing', 'other'])],
        ];
    }
}
