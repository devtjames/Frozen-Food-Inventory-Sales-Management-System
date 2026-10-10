<?php

namespace App\Enums;

enum StockAdjustmentType: string
{
    case Positive = 'positive';
    case Negative = 'negative';

    public function label(): string
    {
        return match ($this) {
            self::Positive => 'Positive correction',
            self::Negative => 'Negative correction',
        };
    }
}
