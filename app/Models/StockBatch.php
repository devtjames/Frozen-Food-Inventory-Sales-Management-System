<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockBatch extends Model
{
    protected $fillable = [
        'product_id',
        'stock_receipt_id',
        'stock_adjustment_id',
        'original_quantity',
        'remaining_quantity',
        'purchase_cost_per_kg',
        'total_purchase_cost',
        'received_at',
        'reference_number',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'original_quantity' => 'decimal:3',
            'remaining_quantity' => 'decimal:3',
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

    /** @return BelongsTo<StockReceipt, $this> */
    public function receipt(): BelongsTo
    {
        return $this->belongsTo(StockReceipt::class, 'stock_receipt_id');
    }

    /** @return BelongsTo<StockAdjustment, $this> */
    public function adjustment(): BelongsTo
    {
        return $this->belongsTo(StockAdjustment::class, 'stock_adjustment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return HasMany<StockAllocation, $this> */
    public function allocations(): HasMany
    {
        return $this->hasMany(StockAllocation::class);
    }

    /** @return HasMany<StockLoss, $this> */
    public function selectedLosses(): HasMany
    {
        return $this->hasMany(StockLoss::class, 'selected_stock_batch_id');
    }
}
