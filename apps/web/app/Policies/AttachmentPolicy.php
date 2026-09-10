<?php

namespace App\Policies;

use App\Models\Attachment;
use App\Models\User;

class AttachmentPolicy
{
    public function before(User $user): ?bool
    {
        return $user->is_super_admin ? true : null;
    }

    public function view(User $user, Attachment $attachment): bool
    {
        return $user->reports()->whereKey($attachment->report_session_id)->exists();
    }
}
