<?php

namespace App\Http\Requests\Auth;

use App\Services\InitialAdministratorService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class InitialAdministratorRequest extends FormRequest
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
        return ! app(InitialAdministratorService::class)->hasAdministrator();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'min:3', 'max:50', 'alpha_dash:ascii', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+() .-]{7,30}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
