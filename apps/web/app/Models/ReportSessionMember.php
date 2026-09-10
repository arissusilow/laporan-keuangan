<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportSessionMember extends Model
{
    use HasFactory;

    protected $fillable = ['report_session_id', 'user_id', 'role', 'can_add_in', 'can_add_out', 'can_edit_own', 'can_edit_all', 'can_cancel', 'can_export_pdf'];

    protected function casts(): array
    {
        return ['can_add_in' => 'boolean', 'can_add_out' => 'boolean', 'can_edit_own' => 'boolean', 'can_edit_all' => 'boolean', 'can_cancel' => 'boolean', 'can_export_pdf' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }
}
