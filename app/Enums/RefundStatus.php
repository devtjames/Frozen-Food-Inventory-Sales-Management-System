<?php

namespace App\Enums;

enum RefundStatus: string
{
    case Completed = 'completed';

    public function label(): string
    {
        return 'Completed';
    }
}
