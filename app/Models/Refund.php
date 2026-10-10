<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\RefundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends Model
{
    protected $fillable = ['sale_reversal_id', 'sale_id', 'amount', 'method', 'status', 'refunded_at', 'recorded_by'];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'status' => RefundStatus::class,
            'refunded_at' => 'datetime',
        ];
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(SaleReversal::class, 'sale_reversal_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
