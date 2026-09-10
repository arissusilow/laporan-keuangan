<?php

namespace App\Policies;

use App\Models\ReportSession;
use App\Models\User;

class ReportSessionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return (bool) $user->active;
    }

    public function view(User $user, ReportSession $report): bool
    {
        return $user->reports()->whereKey($report->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, ReportSession $report): bool
    {
        return $user->reports()->whereKey($report->id)->wherePivot('role', 'ADMIN')->exists();
    }

    public function delete(User $user, ReportSession $report): bool
    {
        return false;
    }

    public function restore(User $user, ReportSession $report): bool
    {
        return false;
    }

    public function forceDelete(User $user, ReportSession $report): bool
    {
        return false;
    }
}
