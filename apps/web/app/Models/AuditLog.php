<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public $timestamps = true;

    protected $fillable = ['user_id', 'report_session_id', 'action', 'target_type', 'target_id', 'before', 'after', 'ip_address'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReportSession::class, 'report_session_id');
    }

    public static function record(string $action, ?Model $target = null, ?array $before = null, ?array $after = null, ?int $reportId = null): self
    {
        return self::recordFor(auth()->id(), $action, $target, $before, $after, $reportId);
    }

    public static function recordFor(?int $userId, string $action, ?Model $target = null, ?array $before = null, ?array $after = null, ?int $reportId = null): self
    {
        return self::create(['user_id' => $userId, 'report_session_id' => $reportId, 'action' => $action, 'target_type' => $target?->getMorphClass(), 'target_id' => $target?->getKey(), 'before' => $before, 'after' => $after, 'ip_address' => request()?->ip()]);
    }
}
