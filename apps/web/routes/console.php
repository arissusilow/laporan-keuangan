<?php

use App\Jobs\CreateBackup;
use App\Models\BackupJob;
use App\Services\ApplicationSettings;
use Illuminate\Support\Facades\Schedule;

$settings = app(ApplicationSettings::class);
$frequency = (string) $settings->get('backup_frequency', 'DAILY');
$time = (string) $settings->get('backup_time', '02:00');
$backupSchedule = Schedule::call(function () use ($frequency): void {
    $job = BackupJob::query()->create(['type' => $frequency, 'status' => 'PENDING']);
    CreateBackup::dispatch($job->id);
})->name('finance-scheduled-backup')->withoutOverlapping();

match ($frequency) {
    'WEEKLY' => $backupSchedule->weeklyOn(1, $time),
    'OFF' => $backupSchedule->skip(fn (): bool => true)->dailyAt($time),
    default => $backupSchedule->dailyAt($time),
};
