<?php

namespace App\Policies;

use App\Models\StockReceipt;
use App\Models\User;

class StockReceiptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, StockReceipt $receipt): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }
}
