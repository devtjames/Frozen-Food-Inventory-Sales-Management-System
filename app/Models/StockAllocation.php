<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockAllocation extends Model
{
    protected $fillable = [
        'stock_batch_id',
        'source_type',
        'source_id',
        'quantity',
        'purchase_cost_per_kg',
        'total_cost',
        'allocated_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'purchase_cost_per_kg' => 'decimal:2',
            'total_cost' => 'decimal:2',
            'allocated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<StockBatch, $this> */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'stock_batch_id');
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
