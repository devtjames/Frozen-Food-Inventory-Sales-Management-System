<?php

namespace App\Policies;

use App\Models\StockLoss;
use App\Models\User;

class StockLossPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, StockLoss $loss): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function reverse(User $user, StockLoss $loss): bool
    {
        return $user->isAdministrator();
    }
}
