<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockReceipt extends Model
{
    protected $fillable = [
        'idempotency_key',
        'product_id',
        'quantity_received',
        'purchase_cost_per_kg',
        'total_purchase_cost',
        'supplier_name',
        'received_at',
        'reference_number',
        'notes',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'decimal:3',
            'purchase_cost_per_kg' => 'decimal:2',
            'total_purchase_cost' => 'decimal:2',
            'received_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return HasOne<StockBatch, $this> */
    public function batch(): HasOne
    {
        return $this->hasOne(StockBatch::class);
    }

    /** @return MorphOne<StockMovement, $this> */
    public function movement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'source');
    }
}
