<?php

namespace App\Policies;

use App\Models\User;

class BackupJobPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_super_admin;
    }
}
