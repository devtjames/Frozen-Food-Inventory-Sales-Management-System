<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->isAdministrator();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdministrator();
    }

    public function manageStatus(User $user, Product $product): bool
    {
        return $user->isAdministrator();
    }
}
