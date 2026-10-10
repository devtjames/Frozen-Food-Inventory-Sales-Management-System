<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use LogicException;

class InitialAdministratorService
{
    public function hasAdministrator(): bool
    {
        return User::query()->where('role', UserRole::Administrator)->exists();
    }

    /** @param array{name: string, username?: string|null, email: string, phone?: string|null, password: string} $attributes */
    public function create(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $administratorExists = User::query()
                ->where('role', UserRole::Administrator)
                ->lockForUpdate()
                ->exists();

            if ($administratorExists) {
                throw new LogicException('The initial administrator has already been created.');
            }

            return User::query()->create([
                'name' => $attributes['name'],
                'username' => $attributes['username'] ?? null,
                'email' => $attributes['email'],
                'phone' => $attributes['phone'] ?? null,
                'role' => UserRole::Administrator,
                'is_active' => true,
                'password' => Hash::make($attributes['password']),
            ]);
        }, 3);
    }
}
