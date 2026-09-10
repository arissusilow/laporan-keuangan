<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = ['report_session_id', 'type', 'name', 'color', 'sort_order', 'active', 'is_default'];

    protected function casts(): array
    {
        return ['active' => 'boolean', 'is_default' => 'boolean', 'sort_order' => 'integer'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'expense_category_id');
    }
}
