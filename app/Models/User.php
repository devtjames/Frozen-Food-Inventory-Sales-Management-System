<?php

namespace App\Models;

use App\Enums\UserRole;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'role',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function isAdministrator(): bool
    {
        return $this->role === UserRole::Administrator;
    }

    public function isCashier(): bool
    {
        return $this->role === UserRole::Cashier;
    }

    public function canProcessSales(): bool
    {
        return $this->is_active && ($this->isAdministrator() || $this->isCashier());
    }

    public function dashboardRoute(): string
    {
        return $this->isAdministrator() ? 'admin.dashboard' : 'cashier.dashboard';
    }

    /** @return HasMany<StockLoss, $this> */
    public function stockLosses(): HasMany
    {
        return $this->hasMany(StockLoss::class, 'recorded_by');
    }

    /** @return HasMany<StockLossReversal, $this> */
    public function stockLossReversals(): HasMany
    {
        return $this->hasMany(StockLossReversal::class, 'recorded_by');
    }

    /** @return HasMany<Sale, $this> */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class, 'cashier_id');
    }

    /** @return HasMany<Payment, $this> */
    public function recordedPayments(): HasMany
    {
        return $this->hasMany(Payment::class, 'recorded_by');
    }

    /** @return HasMany<SaleReversal, $this> */
    public function saleReversals(): HasMany
    {
        return $this->hasMany(SaleReversal::class, 'reversed_by');
    }

    /** @return HasMany<Refund, $this> */
    public function recordedRefunds(): HasMany
    {
        return $this->hasMany(Refund::class, 'recorded_by');
    }

    /** @return HasMany<Expense, $this> */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'recorded_by');
    }

    /** @return HasMany<ExpenseRevision, $this> */
    public function expenseRevisions(): HasMany
    {
        return $this->hasMany(ExpenseRevision::class, 'changed_by');
    }
}
