<?php

namespace App\Models;

use App\Enums\SellingMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SaleItem extends Model
{
    protected $fillable = ['sale_id', 'product_id', 'category_id', 'product_name', 'category_name', 'selling_price_per_kg', 'selling_method', 'requested_amount', 'requested_quantity', 'quantity', 'item_amount', 'calculated_value', 'rounding_adjustment', 'purchase_cost_total'];

    protected function casts(): array
    {
        return [
            'selling_method' => SellingMethod::class,
            'selling_price_per_kg' => 'decimal:2',
            'requested_amount' => 'decimal:2',
            'requested_quantity' => 'decimal:3',
            'quantity' => 'decimal:3',
            'item_amount' => 'decimal:2',
            'calculated_value' => 'decimal:2',
            'rounding_adjustment' => 'decimal:2',
            'purchase_cost_total' => 'decimal:2',
        ];
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function allocations(): MorphMany
    {
        return $this->morphMany(StockAllocation::class, 'source');
    }

    public function movement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'source');
    }

    public function reversalItem(): HasOne
    {
        return $this->hasOne(SaleReversalItem::class);
    }
}
