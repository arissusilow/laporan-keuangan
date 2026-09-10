<?php

namespace App\Services;

use App\Models\BackupJob;
use Illuminate\Support\Facades\DB;
use Throwable;

class OperationalStatusService
{
    /** @return array<string, mixed> */
    public function snapshot(): array
    {
        $database = true;
        try {
            DB::select('SELECT 1');
        } catch (Throwable) {
            $database = false;
        }

        $freeBytes = @disk_free_space(storage_path());

        return [
            'database' => $database,
            'queue_connection' => (string) config('queue.default'),
            'queue_pending' => $database ? DB::table('jobs')->count() : null,
            'queue_failed' => $database ? DB::table('failed_jobs')->count() : null,
            'storage_free_bytes' => is_numeric($freeBytes) ? (int) $freeBytes : null,
            'last_backup' => $database ? BackupJob::query()->latest()->first() : null,
            'checked_at' => now(),
        ];
    }
}
