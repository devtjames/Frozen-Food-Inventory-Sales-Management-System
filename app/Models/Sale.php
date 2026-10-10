<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SaleStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sale extends Model
{
    protected $fillable = ['checkout_key', 'transaction_number', 'cashier_id', 'sold_at', 'total_amount', 'total_rounding_adjustment', 'payment_method', 'payment_status', 'transaction_status', 'amount_tendered', 'change_due'];

    protected function casts(): array
    {
        return [
            'sold_at' => 'datetime',
            'total_amount' => 'decimal:2',
            'total_rounding_adjustment' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'transaction_status' => SaleStatus::class,
            'amount_tendered' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /** @return HasMany<SaleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /** @return HasOne<Payment, $this> */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(SaleReversal::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }

    public function isReversed(): bool
    {
        return $this->relationLoaded('reversal') ? $this->reversal !== null : $this->reversal()->exists();
    }

    public function effectiveTransactionStatus(): string
    {
        return $this->isReversed() ? 'Reversed' : $this->transaction_status->label();
    }

    public function effectivePaymentStatus(): string
    {
        return $this->isReversed() ? 'Refunded' : $this->payment_status->label();
    }
}
