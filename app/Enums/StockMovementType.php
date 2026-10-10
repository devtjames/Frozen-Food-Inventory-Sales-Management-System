<?php

namespace App\Enums;

enum StockMovementType: string
{
    case StockReceived = 'stock_received';
    case Sale = 'sale';
    case Spoiled = 'spoiled';
    case Damaged = 'damaged';
    case StockLoss = 'stock_loss';
    case StockLossReversal = 'stock_loss_reversal';
    case PositiveAdjustment = 'positive_adjustment';
    case NegativeAdjustment = 'negative_adjustment';
    case SaleReversal = 'sale_reversal';

    public function label(): string
    {
        return match ($this) {
            self::StockReceived => 'Stock received',
            self::Sale => 'Sale',
            self::Spoiled => 'Spoiled stock',
            self::Damaged => 'Damaged stock',
            self::StockLoss => 'Stock loss',
            self::StockLossReversal => 'Stock loss reversal',
            self::PositiveAdjustment => 'Positive adjustment',
            self::NegativeAdjustment => 'Negative adjustment',
            self::SaleReversal => 'Sale reversal',
        };
    }
}
