<?php

namespace App\Models;

use App\Enums\StockAdjustmentType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class StockAdjustment extends Model
{
    protected $fillable = [
        'idempotency_key',
        'product_id',
        'type',
        'quantity',
        'cost_basis_per_kg',
        'total_cost_impact',
        'reason',
        'adjusted_at',
        'reference_number',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => StockAdjustmentType::class,
            'quantity' => 'decimal:3',
            'cost_basis_per_kg' => 'decimal:2',
            'total_cost_impact' => 'decimal:2',
            'adjusted_at' => 'datetime',
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

    /** @return MorphMany<StockAllocation, $this> */
    public function allocations(): MorphMany
    {
        return $this->morphMany(StockAllocation::class, 'source');
    }
}
