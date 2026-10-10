<?php

namespace App\Policies;

use App\Models\Sale;
use App\Models\User;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator() || $user->isCashier();
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->isAdministrator() || ($user->isCashier() && $sale->cashier_id === $user->id);
    }

    public function reverse(User $user, Sale $sale): bool
    {
        return $user->isAdministrator();
    }
}
