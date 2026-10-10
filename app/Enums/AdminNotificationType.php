<?php

namespace App\Enums;

enum AdminNotificationType: string
{
    case LowStock = 'low_stock';
    case OutOfStock = 'out_of_stock';
    case StockAdjustment = 'stock_adjustment';
    case StockLoss = 'stock_loss';
    case Activity = 'activity';

    public function label(): string
    {
        return match ($this) {
            self::LowStock => 'Low stock',
            self::OutOfStock => 'Out of stock',
            self::StockAdjustment => 'Stock adjustment',
            self::StockLoss => 'Stock loss',
            self::Activity => 'Activity',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::OutOfStock, self::StockLoss => 'danger',
            self::LowStock, self::StockAdjustment => 'warning',
            self::Activity => 'info',
        };
    }
}
