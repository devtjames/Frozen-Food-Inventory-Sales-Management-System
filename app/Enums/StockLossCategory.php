<?php

namespace App\Enums;

enum StockLossCategory: string
{
    case Spoiled = 'spoiled';
    case Damaged = 'damaged';
    case FreezerFailure = 'freezer_failure';
    case Missing = 'missing';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Spoiled => 'Spoiled',
            self::Damaged => 'Damaged',
            self::FreezerFailure => 'Freezer failure',
            self::Missing => 'Missing stock',
            self::Other => 'Other',
        };
    }

    public function movementType(): StockMovementType
    {
        return match ($this) {
            self::Spoiled => StockMovementType::Spoiled,
            self::Damaged => StockMovementType::Damaged,
            default => StockMovementType::StockLoss,
        };
    }
}
