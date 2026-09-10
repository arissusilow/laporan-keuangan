<?php

namespace App\Policies;

use App\Models\ApplicationSetting;
use App\Models\User;

class ApplicationSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function update(User $user, ApplicationSetting $setting): bool
    {
        return $user->is_super_admin;
    }
}
