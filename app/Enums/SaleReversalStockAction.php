<?php

namespace App\Enums;

enum SaleReversalStockAction: string
{
    case Restore = 'restore';
    case DoNotRestore = 'do_not_restore';

    public function label(): string
    {
        return match ($this) {
            self::Restore => 'Mistaken sale / sellable stock returned',
            self::DoNotRestore => 'Not returned / unsuitable for resale',
        };
    }

    public function restoresStock(): bool
    {
        return $this === self::Restore;
    }
}
