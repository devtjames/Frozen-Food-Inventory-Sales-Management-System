<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockLossReversal extends Model
{
    protected $fillable = [
        'idempotency_key',
        'stock_loss_id',
        'reason',
        'reversed_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return ['reversed_at' => 'datetime'];
    }

    /** @return BelongsTo<StockLoss, $this> */
    public function loss(): BelongsTo
    {
        return $this->belongsTo(StockLoss::class, 'stock_loss_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return MorphOne<StockMovement, $this> */
    public function movement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'source');
    }
}
