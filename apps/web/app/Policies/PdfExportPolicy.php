<?php

namespace App\Policies;

use App\Models\PdfExport;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;

class PdfExportPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function view(User $user, PdfExport $export): bool
    {
        return $user->reports()->whereKey($export->report_session_id)->exists();
    }

    public function create(User $user, ReportSession $report): bool
    {
        $member = ReportSessionMember::query()->whereBelongsTo($user)->whereBelongsTo($report, 'report')->first();

        return $member !== null && ($member->role === 'ADMIN' || $member->can_export_pdf);
    }
}
