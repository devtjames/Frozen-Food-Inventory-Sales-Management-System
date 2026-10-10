<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class SaleReversalItem extends Model
{
    protected $fillable = ['sale_reversal_id', 'sale_item_id', 'product_id', 'quantity', 'refund_amount', 'purchase_cost_total', 'stock_restored'];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'refund_amount' => 'decimal:2',
            'purchase_cost_total' => 'decimal:2',
            'stock_restored' => 'boolean',
        ];
    }

    public function reversal(): BelongsTo
    {
        return $this->belongsTo(SaleReversal::class, 'sale_reversal_id');
    }

    public function saleItem(): BelongsTo
    {
        return $this->belongsTo(SaleItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function movement(): MorphOne
    {
        return $this->morphOne(StockMovement::class, 'source');
    }
}
