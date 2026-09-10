<?php

namespace App\Policies;

use App\Models\ReportSession;
use App\Models\User;

class SlideConfigPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function update(User $user, ReportSession $report): bool
    {
        return $user->reports()->whereKey($report->id)->wherePivot('role', 'ADMIN')->exists();
    }
}
