<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $table = 'transactions';

    protected $fillable = ['report_session_id', 'expense_category_id', 'created_by', 'updated_by', 'number', 'transaction_date', 'type', 'amount', 'description', 'status', 'cancellation_reason', 'cancelled_at', 'cancelled_by'];

    protected function casts(): array
    {
        return ['transaction_date' => 'date', 'amount' => 'integer', 'cancelled_at' => 'datetime'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class, 'transaction_id');
    }
}
