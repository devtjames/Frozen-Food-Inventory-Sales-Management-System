<?php

namespace App\Models;

use App\Enums\StockAlertState;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'normalized_name',
        'description',
        'image_path',
        'selling_price',
        'low_stock_threshold',
        'stock_alert_state',
        'is_active',
    ];

    protected $hidden = [
        'normalized_name',
    ];

    protected function casts(): array
    {
        return [
            'selling_price' => 'decimal:2',
            'low_stock_threshold' => 'decimal:3',
            'stock_alert_state' => StockAlertState::class,
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @param Builder<Product> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Product> $query */
    public function scopeAvailableForSale(Builder $query): void
    {
        $query->active()->whereHas('category', fn (Builder $query) => $query->active());
    }

    public function isAvailableForNewSales(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->relationLoaded('category')) {
            return $this->category?->is_active === true;
        }

        return $this->category()->where('is_active', true)->exists();
    }

    /** @return HasMany<StockReceipt, $this> */
    public function stockReceipts(): HasMany
    {
        return $this->hasMany(StockReceipt::class);
    }

    /** @return HasMany<StockBatch, $this> */
    public function stockBatches(): HasMany
    {
        return $this->hasMany(StockBatch::class);
    }

    /** @return HasMany<StockMovement, $this> */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /** @return HasMany<StockAdjustment, $this> */
    public function stockAdjustments(): HasMany
    {
        return $this->hasMany(StockAdjustment::class);
    }

    /** @return HasMany<StockLoss, $this> */
    public function stockLosses(): HasMany
    {
        return $this->hasMany(StockLoss::class);
    }

    /** @return HasMany<SaleItem, $this> */
    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}
