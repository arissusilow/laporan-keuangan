<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateApplicationSettingsRequest;
use App\Models\ApplicationSetting;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\ApplicationSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ApplicationSettingController extends Controller
{
    public function index(ApplicationSettings $settings): View
    {
        Gate::authorize('viewAny', ApplicationSetting::class);

        return view('admin.application-settings', [
            'settings' => $settings->all(),
            'users' => User::query()->withCount('reportMemberships')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateApplicationSettingsRequest $request, ApplicationSettings $settings): RedirectResponse
    {
        $values = $request->safe()->except('app_logo');
        $before = ApplicationSetting::query()->pluck('value', 'key')->all();
        $requestedTab = $request->string('_settings_tab')->toString();
        $activeTab = in_array($requestedTab, ['identity', 'regional', 'security', 'uploads'], true) ? $requestedTab : 'identity';

        if ($request->hasFile('app_logo')) {
            $oldPath = $before['logo_path'] ?? null;
            $values['logo_path'] = $request->file('app_logo')->store('application-identity', 'local');
            if (is_string($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
        }

        DB::transaction(function () use ($values, $before): void {
            foreach ($values as $key => $value) {
                ApplicationSetting::query()->updateOrCreate(
                    ['key' => $key],
                    ['group' => ApplicationSettings::GROUPS[$key], 'value' => $value],
                );
            }
            AuditLog::record('APPLICATION_SETTINGS_UPDATED', null, $before, $values);
        });
        $settings->forget();

        return redirect()->to(route('admin.settings.index').'#'.$activeTab)->with('success', 'Konfigurasi Aplikasi disimpan.');
    }
}
