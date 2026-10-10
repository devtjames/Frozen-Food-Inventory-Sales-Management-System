<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateCashierRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'username' => $this->filled('username') ? Str::lower(trim((string) $this->input('username'))) : null,
            'email' => Str::lower(trim((string) $this->input('email'))),
            'phone' => $this->filled('phone') ? trim((string) $this->input('phone')) : null,
        ]);
    }

    public function authorize(): bool
    {
        $staff = $this->route('staff');

        return $staff instanceof User && ($this->user()?->can('update', $staff) ?? false);
    }

    public function rules(): array
    {
        $staff = $this->route('staff');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', Rule::unique('users', 'username')->ignore($staff)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($staff)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]{7,30}$/'],
        ];
    }
}
