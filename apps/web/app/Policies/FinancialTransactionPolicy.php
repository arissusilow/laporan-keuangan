<?php

namespace App\Policies;

use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function view(User $user, FinancialTransaction $transaction): bool
    {
        return $user->reports()->whereKey($transaction->report_session_id)->exists();
    }

    public function create(User $user, ReportSession $report, string $type): bool
    {
        if (! $report->isWritable()) {
            return false;
        }

        $member = $this->member($user, $report);
        $permission = $type === 'IN' ? 'can_add_in' : 'can_add_out';

        return $member !== null && ($member->role === 'ADMIN' || $member->{$permission});
    }

    public function update(User $user, FinancialTransaction $transaction): bool
    {
        if (! $transaction->report->isWritable() || $transaction->status !== 'ACTIVE') {
            return false;
        }

        $member = $this->member($user, $transaction->report);

        return $member !== null && ($member->role === 'ADMIN' || $member->can_edit_all || ($member->can_edit_own && $transaction->created_by === $user->id));
    }

    public function cancel(User $user, FinancialTransaction $transaction): bool
    {
        if (! $transaction->report->isWritable() || $transaction->status !== 'ACTIVE') {
            return false;
        }

        $member = $this->member($user, $transaction->report);

        return $member !== null && ($member->role === 'ADMIN' || $member->can_cancel);
    }

    private function member(User $user, ReportSession $report): ?ReportSessionMember
    {
        return ReportSessionMember::query()->whereBelongsTo($user)->whereBelongsTo($report, 'report')->first();
    }
}
