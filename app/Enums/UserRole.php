<?php

namespace App\Enums;

enum UserRole: string
{
    case Administrator = 'administrator';
    case Cashier = 'cashier';

    public function label(): string
    {
        return match ($this) {
            self::Administrator => 'Administrator',
            self::Cashier => 'Cashier',
        };
    }
}
