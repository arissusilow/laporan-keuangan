<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Services\ApplicationSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Symfony\Component\Process\Process;
use Throwable;

class CreateBackup implements ShouldQueue
{
    use Queueable;

    public int $timeout = 600;

    public function __construct(public int $jobId) {}

    public function handle(ApplicationSettings $settings): void
    {
        $job = BackupJob::findOrFail($this->jobId);
        $job->update(['status' => 'PROCESSING']);

        $base = rtrim(config('finance.backup_path'), '/');
        if (! is_dir($base)) {
            mkdir($base, 0700, true);
        }

        $privatePath = storage_path('app/private');
        if (! is_dir($privatePath)) {
            mkdir($privatePath, 0700, true);
        }

        $stamp = now()->format('Ymd-His');
        $databaseName = "database-{$stamp}.dump";
        $filesName = "private-files-{$stamp}.tar.gz";
        $manifestName = "manifest-{$stamp}.json";
        $checksumName = "backup-{$stamp}.sha256";
        $databasePath = "{$base}/{$databaseName}";
        $filesPath = "{$base}/{$filesName}";
        $manifestPath = "{$base}/{$manifestName}";

        $dump = new Process([
            'pg_dump', '--format=custom', '--file='.$databasePath,
            '--host='.config('database.connections.pgsql.host'),
            '--port='.(string) config('database.connections.pgsql.port'),
            '--username='.config('database.connections.pgsql.username'),
            config('database.connections.pgsql.database'),
        ]);
        $dump->setEnv(['PGPASSWORD' => (string) config('database.connections.pgsql.password')]);
        $dump->setTimeout(540);
        $dump->mustRun();

        $archive = new Process(['tar', '-czf', $filesPath, '-C', $privatePath, '.']);
        $archive->setTimeout(540);
        $archive->mustRun();

        $databaseChecksum = hash_file('sha256', $databasePath);
        $filesChecksum = hash_file('sha256', $filesPath);
        file_put_contents($manifestPath, json_encode([
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'database' => ['file' => $databaseName, 'sha256' => $databaseChecksum],
            'private_files' => ['file' => $filesName, 'sha256' => $filesChecksum],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL, LOCK_EX);

        $manifestChecksum = hash_file('sha256', $manifestPath);
        file_put_contents("{$base}/{$checksumName}", implode(PHP_EOL, [
            "{$databaseChecksum}  {$databaseName}",
            "{$filesChecksum}  {$filesName}",
            "{$manifestChecksum}  {$manifestName}",
            '',
        ]), LOCK_EX);

        foreach ([
            [$databasePath, $databaseChecksum],
            [$filesPath, $filesChecksum],
            [$manifestPath, $manifestChecksum],
        ] as [$artifact, $expected]) {
            if (! hash_equals($expected, hash_file('sha256', $artifact))) {
                throw new \RuntimeException('Verifikasi checksum backup gagal.');
            }
        }

        $job->update([
            'status' => 'SUCCESS',
            'path' => $databasePath,
            'checksum' => $databaseChecksum,
            'completed_at' => now(),
        ]);
        AuditLog::recordFor($job->requested_by, 'BACKUP_COMPLETED', $job, null, [
            'type' => $job->type,
            'checksum' => $databaseChecksum,
        ]);

        $keep = $settings->integer('backup_retention_daily', (int) config('finance.backup_retention_daily', 14));
        foreach (array_slice(array_reverse(glob($base.'/database-*.dump')), $keep) as $old) {
            $oldStamp = str_replace(['database-', '.dump'], '', basename($old));
            foreach ([
                $old,
                "{$base}/private-files-{$oldStamp}.tar.gz",
                "{$base}/manifest-{$oldStamp}.json",
                "{$base}/backup-{$oldStamp}.sha256",
            ] as $artifact) {
                @unlink($artifact);
            }
        }
    }

    public function failed(Throwable $e): void
    {
        $job = BackupJob::find($this->jobId);
        $job?->update([
            'status' => 'FAILED',
            'error' => 'Backup gagal; periksa log server.',
        ]);
        if ($job) {
            AuditLog::recordFor($job->requested_by, 'BACKUP_FAILED', $job, null, ['message' => 'Backup gagal; periksa log server.']);
        }
    }
}
