<?php

namespace App\Policies;

use App\Models\ExpenseCategory;
use App\Models\ReportSession;
use App\Models\User;

class ExpenseCategoryPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function create(User $user, ReportSession $report): bool
    {
        return $user->reports()->whereKey($report->id)->wherePivot('role', 'ADMIN')->exists();
    }

    public function update(User $user, ExpenseCategory $category): bool
    {
        return $user->reports()->whereKey($category->report_session_id)->wherePivot('role', 'ADMIN')->exists();
    }
}
