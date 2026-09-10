<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_super_admin;
    }

    public function view(User $user, AuditLog $auditLog): bool
    {
        if ($user->is_super_admin) {
            return true;
        }

        return $auditLog->report_session_id !== null
            && $user->reports()->whereKey($auditLog->report_session_id)->wherePivot('role', 'ADMIN')->exists();
    }
}
