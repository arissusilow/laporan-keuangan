<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReportSession extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'starts_on', 'ends_on', 'opening_balance', 'currency', 'color', 'logo_path', 'pdf_settings', 'status'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'opening_balance' => 'integer', 'pdf_settings' => 'array'];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'report_session_members')->withPivot(['role', 'can_add_in', 'can_add_out', 'can_edit_own', 'can_edit_all', 'can_cancel', 'can_export_pdf']);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ReportSessionMember::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    public function pdfExports(): HasMany
    {
        return $this->hasMany(PdfExport::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function slideConfig(): HasOne
    {
        return $this->hasOne(SlideConfig::class);
    }

    public function isWritable(): bool
    {
        return $this->status === 'ACTIVE';
    }
}
