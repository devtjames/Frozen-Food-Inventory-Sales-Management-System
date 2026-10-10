<?php

namespace App\Models;

use App\Enums\SaleReversalStockAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SaleReversal extends Model
{
    protected $fillable = ['idempotency_key', 'sale_id', 'stock_action', 'reason', 'reversed_at', 'reversed_by'];

    protected function casts(): array
    {
        return [
            'stock_action' => SaleReversalStockAction::class,
            'reversed_at' => 'datetime',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function administrator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleReversalItem::class);
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class);
    }
}
