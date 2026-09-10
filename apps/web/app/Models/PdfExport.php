<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PdfExport extends Model
{
    use HasFactory;

    protected $fillable = ['report_session_id', 'requested_by', 'starts_on', 'ends_on', 'transaction_type', 'expense_category_id', 'status', 'path', 'error'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
