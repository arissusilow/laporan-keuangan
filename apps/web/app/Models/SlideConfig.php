<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlideConfig extends Model
{
    use HasFactory;

    protected $fillable = ['report_session_id', 'enabled', 'token_hash', 'public_token', 'starts_on', 'ends_on', 'duration_seconds', 'refresh_seconds', 'show_latest_transactions', 'settings'];

    protected $hidden = ['token_hash', 'public_token'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'show_latest_transactions' => 'boolean', 'public_token' => 'encrypted', 'starts_on' => 'date', 'ends_on' => 'date', 'settings' => 'array'];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }
}
