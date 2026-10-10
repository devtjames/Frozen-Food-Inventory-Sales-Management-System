<?php

namespace App\Models;

use App\Enums\StockLossCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockLoss extends Model
{
    protected $fillable = [
        'idempotency_key',
        'product_id',
        'selected_stock_batch_id',
        'category',
        'quantity_lost',
        'financial_value',
        'reason',
        'description',
        'lost_at',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'category' => StockLossCategory::class,
            'quantity_lost' => 'decimal:3',
            'financial_value' => 'decimal:2',
            'lost_at' => 'datetime',
        ];
    }

    /** @param Builder<StockLoss> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereDoesntHave('reversal');
    }

    /** @param Builder<StockLoss> $query */
    public function scopeReversed(Builder $query): void
    {
        $query->whereHas('reversal');
    }

    public function isReversed(): bool
    {
        return $this->relationLoaded('reversal')
            ? $this->reversal !== null
            : $this->reversal()->exists();
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** @return BelongsTo<StockBatch, $this> */
    public function selectedBatch(): BelongsTo
    {
        return $this->belongsTo(StockBatch::class, 'selected_stock_batch_id');
    }

    /** @return BelongsTo<User, $this> */
    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    /** @return MorphMany<StockAllocation, $this> */
    public function allocations(): MorphMany
    {
        return $this->morphMany(StockAllocation::class, 'source');
    }

    /** @return MorphOne<StockMovement, $this> */
    public function movement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'source');
    }

    /** @return HasOne<StockLossReversal, $this> */
    public function reversal(): HasOne
    {
        return $this->hasOne(StockLossReversal::class);
    }
}
