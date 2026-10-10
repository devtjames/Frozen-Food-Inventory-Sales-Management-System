<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Paid = 'paid';

    public function label(): string
    {
        return 'Paid';
    }
}
