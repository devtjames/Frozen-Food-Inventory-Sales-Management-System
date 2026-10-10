<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function view(User $user, User $staff): bool
    {
        return $user->isAdministrator() && $staff->isCashier();
    }

    public function create(User $user): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, User $staff): bool
    {
        return $this->view($user, $staff);
    }

    public function manageStatus(User $user, User $staff): bool
    {
        return $this->view($user, $staff);
    }

    public function resetPassword(User $user, User $staff): bool
    {
        return $this->view($user, $staff);
    }
}
