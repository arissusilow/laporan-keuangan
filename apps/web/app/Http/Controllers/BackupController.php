<?php

namespace App\Http\Controllers;

use App\Jobs\CreateBackup;
use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Services\ApplicationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BackupController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', BackupJob::class);
        $job = BackupJob::create(['requested_by' => $request->user()->id, 'type' => 'MANUAL', 'status' => 'PENDING']);
        CreateBackup::dispatch($job->id);
        AuditLog::record('BACKUP_QUEUED', $job, null, $job->toArray());

        return back()->with('success', 'Backup masuk antrean.');
    }

    public function updateSettings(Request $request, ApplicationSettings $settings): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', BackupJob::class);
        $data = $request->validate([
            'backup_frequency' => ['required', Rule::in(['OFF', 'DAILY', 'WEEKLY'])],
            'backup_time' => ['required', 'date_format:H:i'],
            'backup_retention_daily' => ['required', 'integer', 'min:1', 'max:365'],
        ]);
        $before = collect($settings->all())->only(array_keys($data))->all();

        DB::transaction(function () use ($data, $before): void {
            foreach ($data as $key => $value) {
                ApplicationSetting::query()->updateOrCreate(['key' => $key], [
                    'group' => ApplicationSettings::GROUPS[$key],
                    'value' => $value,
                ]);
            }
            AuditLog::record('BACKUP_SETTINGS_UPDATED', null, $before, $data);
        });
        $settings->forget();

        return back()->with('success', 'Jadwal dan retensi backup disimpan.');
    }
}
