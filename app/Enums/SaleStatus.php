<?php

namespace App\Enums;

enum SaleStatus: string
{
    case Completed = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
