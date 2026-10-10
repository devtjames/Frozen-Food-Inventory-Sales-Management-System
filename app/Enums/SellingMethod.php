<?php

namespace App\Enums;

enum SellingMethod: string
{
    case Amount = 'amount';
    case Kilograms = 'kilograms';

    public function label(): string
    {
        return $this === self::Amount ? 'Sell by amount' : 'Sell by kilograms';
    }
}
