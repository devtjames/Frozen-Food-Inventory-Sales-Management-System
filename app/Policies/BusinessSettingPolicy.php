<?php

namespace App\Policies;

use App\Models\BusinessSetting;
use App\Models\User;

class BusinessSettingPolicy
{
    public function view(User $user, BusinessSetting $settings): bool
    {
        return $user->isAdministrator();
    }

    public function update(User $user, BusinessSetting $settings): bool
    {
        return $user->isAdministrator();
    }
}
