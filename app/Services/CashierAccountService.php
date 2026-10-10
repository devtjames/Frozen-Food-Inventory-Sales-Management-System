<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

class CashierAccountService
{
    /**
     * @param  array{name: string, username?: string|null, email: string, phone?: string|null, password: string, is_active: bool}  $attributes
     */
    public function create(array $attributes): User
    {
        return DB::transaction(fn () => User::query()->create([
            'name' => $attributes['name'],
            'username' => $attributes['username'] ?? null,
            'email' => $attributes['email'],
            'phone' => $attributes['phone'] ?? null,
            'password' => Hash::make($attributes['password']),
            'role' => UserRole::Cashier,
            'is_active' => $attributes['is_active'],
        ]));
    }

    /**
     * @param  array{name: string, username?: string|null, email: string, phone?: string|null}  $attributes
     */
    public function updateDetails(User $cashier, array $attributes): User
    {
        $this->ensureCashier($cashier);

        $cashier->update([
            'name' => $attributes['name'],
            'username' => $attributes['username'] ?? null,
            'email' => $attributes['email'],
            'phone' => $attributes['phone'] ?? null,
        ]);

        return $cashier->refresh();
    }

    public function setActive(User $cashier, bool $isActive): User
    {
        return DB::transaction(function () use ($cashier, $isActive): User {
            $lockedCashier = User::query()->lockForUpdate()->findOrFail($cashier->getKey());
            $this->ensureCashier($lockedCashier);

            $updates = ['is_active' => $isActive];

            if (! $isActive) {
                $updates['remember_token'] = Str::random(60);
                $this->deleteSessions($lockedCashier);
            }

            $lockedCashier->update($updates);

            return $lockedCashier->refresh();
        });
    }

    public function resetPassword(User $cashier, string $password): User
    {
        return DB::transaction(function () use ($cashier, $password): User {
            $lockedCashier = User::query()->lockForUpdate()->findOrFail($cashier->getKey());
            $this->ensureCashier($lockedCashier);

            $lockedCashier->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            $this->deleteSessions($lockedCashier);

            return $lockedCashier->refresh();
        });
    }

    private function deleteSessions(User $cashier): void
    {
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $cashier->getKey())
            ->delete();
    }

    private function ensureCashier(User $user): void
    {
        if (! $user->isCashier()) {
            throw new LogicException('Only cashier accounts can be managed through staff management.');
        }
    }
}
