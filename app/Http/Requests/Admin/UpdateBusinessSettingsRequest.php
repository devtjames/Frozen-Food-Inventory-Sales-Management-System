<?php

namespace App\Http\Requests\Admin;

use App\Services\BusinessSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateBusinessSettingsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'business_name' => trim((string) $this->input('business_name')),
            'phone' => trim((string) $this->input('phone')),
            'email' => $this->filled('email') ? mb_strtolower(trim((string) $this->input('email'))) : null,
            'address' => trim((string) $this->input('address')),
            'receipt_footer_message' => $this->filled('receipt_footer_message')
                ? trim((string) $this->input('receipt_footer_message'))
                : null,
        ]);
    }

    public function authorize(): bool
    {
        $settings = app(BusinessSettingsService::class)->get();

        return $this->user()?->can('update', $settings) ?? false;
    }

    public function rules(): array
    {
        return [
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+() .-]{7,30}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'address' => ['required', 'string', 'max:1000'],
            'receipt_footer_message' => ['nullable', 'string', 'max:500'],
            'currency_code' => ['required', Rule::in(['NGN'])],
            'default_low_stock_threshold' => ['required', 'decimal:0,3', 'min:0', 'max:999999999.999'],
            'show_logo_on_receipts' => ['required', 'boolean'],
            'remove_logo' => ['sometimes', 'boolean'],
            'logo' => [
                'nullable',
                File::image()
                    ->types(['jpg', 'jpeg', 'png', 'webp'])
                    ->max((int) config('business.logo_max_kilobytes')),
                'dimensions:max_width=3000,max_height=3000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.dimensions' => 'The logo must not be larger than 3000 by 3000 pixels.',
            'logo.max' => 'The logo must not be larger than '.config('business.logo_max_kilobytes').' KB.',
        ];
    }
}
