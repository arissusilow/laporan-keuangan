<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\StoreReportMemberRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Http\Requests\UpdateSlideConfigRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\AuditLog;
use App\Models\BackupJob;
use App\Models\ExpenseCategory;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use App\Services\ApplicationSettings;
use App\Services\OperationalStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function system(OperationalStatusService $statuses, ApplicationSettings $settings): View
    {
        Gate::authorize('viewAny', BackupJob::class);

        return view('admin.system', [
            'users' => User::query()->orderBy('name')->get(),
            'backups' => BackupJob::query()->with('requester')->latest()->limit(20)->get(),
            'audits' => AuditLog::query()->with(['user', 'report'])->latest()->limit(50)->get(),
            'reportCount' => ReportSession::query()->count(),
            'systemStatus' => $statuses->snapshot(),
            'backupSettings' => $settings->all(),
        ]);
    }

    public function userStore(StoreUserRequest $request): JsonResponse|RedirectResponse
    {
        $data = $request->validated();
        $initialPassword = $request->boolean('use_default_password')
            ? config('finance.initial_user_password')
            : ($data['password'] ?? null);

        if (! is_string($initialPassword) || Str::length($initialPassword) < 6) {
            throw ValidationException::withMessages([
                'password' => 'INITIAL_USER_PASSWORD pada .env wajib diisi minimal 6 karakter.',
            ]);
        }

        $user = DB::transaction(function () use ($data, $request, $initialPassword): User {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => mb_strtolower($data['email']),
                'password' => Hash::make($initialPassword),
                'is_super_admin' => $request->boolean('is_super_admin'),
                'active' => true,
                'must_change_password' => true,
            ]);
            AuditLog::record('USER_CREATED', $user, null, ['email' => $user->email, 'is_super_admin' => $user->is_super_admin]);

            return $user;
        });

        $message = 'Pengguna dibuat dan wajib mengganti kata sandi saat login pertama.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'redirect_url' => route('admin.settings.index').'#users',
            ], 201);
        }

        return redirect()->to(route('admin.settings.index').'#users')->with('success', $message);
    }

    public function userUpdate(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $willRemainSuperAdmin = $request->boolean('is_super_admin');
        $willRemainActive = $request->boolean('active');
        if ($user->is_super_admin && (! $willRemainSuperAdmin || ! $willRemainActive) && User::query()->where('is_super_admin', true)->where('active', true)->count() <= 1) {
            throw ValidationException::withMessages(['active' => 'Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan.']);
        }

        DB::transaction(function () use ($request, $user, $willRemainSuperAdmin, $willRemainActive): void {
            $before = $user->only(['name', 'email', 'active', 'is_super_admin']);
            $user->update([
                'name' => $request->validated('name'),
                'email' => mb_strtolower($request->validated('email')),
                'active' => $willRemainActive,
                'is_super_admin' => $willRemainSuperAdmin,
            ]);
            AuditLog::record('USER_UPDATED', $user, $before, $user->fresh()->only(['name', 'email', 'active', 'is_super_admin']));
        });

        return back()->with('success', 'Pengguna diperbarui.');
    }

    public function userPasswordReset(Request $request, User $user): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $user);
        $initialPassword = config('finance.initial_user_password');

        if (! is_string($initialPassword) || Str::length($initialPassword) < 6) {
            throw ValidationException::withMessages([
                'password' => 'INITIAL_USER_PASSWORD pada .env wajib diisi minimal 6 karakter.',
            ]);
        }

        DB::transaction(function () use ($user, $initialPassword): void {
            $user->update([
                'password' => Hash::make($initialPassword),
                'must_change_password' => true,
            ]);
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            AuditLog::record('PASSWORD_RESET_TO_INITIAL_BY_ADMIN', $user);
        });

        return back()->with('success', 'Kata sandi dikembalikan ke password awal. Pengguna wajib menggantinya setelah login.');
    }

    public function categoryStore(StoreExpenseCategoryRequest $request, ReportSession $report): RedirectResponse
    {
        $category = DB::transaction(function () use ($request, $report): ExpenseCategory {
            if ($request->boolean('is_default')) {
                $report->categories()->where('type', $request->validated('type'))->update(['is_default' => false]);
            }
            $category = $report->categories()->create($request->safe()->only(['type', 'name', 'color', 'sort_order']) + [
                'active' => true,
                'is_default' => $request->boolean('is_default'),
            ]);
            AuditLog::record('CATEGORY_CREATED', $category, null, $category->toArray(), $report->id);

            return $category;
        });

        return back()->with('success', "Kategori {$category->name} ditambahkan.");
    }

    public function categoryUpdate(UpdateExpenseCategoryRequest $request, ReportSession $report, ExpenseCategory $category): RedirectResponse
    {
        abort_unless($category->report_session_id === $report->id, 404);
        if ($request->validated('type') !== $category->type && $category->transactions()->exists()) {
            throw ValidationException::withMessages(['type' => 'Jenis kategori yang sudah dipakai transaksi tidak dapat diubah.']);
        }
        DB::transaction(function () use ($request, $report, $category): void {
            if ($request->boolean('is_default')) {
                $report->categories()->where('type', $request->validated('type'))->whereKeyNot($category->id)->update(['is_default' => false]);
            }
            $before = $category->toArray();
            $category->update($request->safe()->only(['type', 'name', 'color', 'sort_order']) + [
                'active' => $request->boolean('active'),
                'is_default' => $request->boolean('is_default'),
            ]);
            AuditLog::record('CATEGORY_UPDATED', $category, $before, $category->fresh()->toArray(), $report->id);
        });

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function categoryDestroy(Request $request, ReportSession $report, ExpenseCategory $category): RedirectResponse
    {
        abort_unless($category->report_session_id === $report->id, 404);
        Gate::forUser($request->user())->authorize('update', $category);

        if ($category->transactions()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'Kategori sudah dipakai transaksi sehingga tidak dapat dihapus. Ubah kategori menjadi nonaktif agar riwayat tetap utuh.',
            ]);
        }

        DB::transaction(function () use ($report, $category): void {
            AuditLog::record('CATEGORY_DELETED', $category, $category->toArray(), null, $report->id);
            $category->delete();
        });

        return back()->with('success', 'Kategori yang belum pernah dipakai berhasil dihapus.');
    }

    public function memberStore(StoreReportMemberRequest $request, ReportSession $report): RedirectResponse
    {
        $member = $this->saveMember($request, $report);

        return back()->with('success', "Akses {$member->user->name} disimpan.");
    }

    public function memberUpdate(StoreReportMemberRequest $request, ReportSession $report, ReportSessionMember $member): RedirectResponse
    {
        abort_unless($member->report_session_id === $report->id, 404);
        abort_unless($member->user?->email === mb_strtolower($request->validated('email')), 422);

        $updatedMember = $this->saveMember($request, $report);

        return back()->with('success', "Hak akses {$updatedMember->user->name} diperbarui.");
    }

    public function memberDestroy(Request $request, ReportSession $report, ReportSessionMember $member): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('update', $report);
        abort_unless($member->report_session_id === $report->id, 404);

        if ($member->role === 'ADMIN' && $report->members()->where('role', 'ADMIN')->count() <= 1) {
            throw ValidationException::withMessages([
                'member' => 'Admin Laporan terakhir tidak dapat dicabut. Jadikan anggota lain sebagai Admin Laporan terlebih dahulu.',
            ]);
        }

        $name = $member->user?->name ?? 'anggota';
        DB::transaction(function () use ($report, $member): void {
            AuditLog::record('MEMBER_REMOVED', $member, $member->toArray(), null, $report->id);
            $member->delete();
        });

        return back()->with('success', "Akses {$name} dicabut dari Laporan ini.");
    }

    private function saveMember(StoreReportMemberRequest $request, ReportSession $report): ReportSessionMember
    {
        $data = $request->validated();
        $user = User::query()->where('email', mb_strtolower($data['email']))->firstOrFail();
        $permissions = collect(['can_add_in', 'can_add_out', 'can_edit_own', 'can_edit_all', 'can_cancel', 'can_export_pdf'])
            ->mapWithKeys(fn (string $permission): array => [$permission => $request->boolean($permission)])
            ->all();

        return DB::transaction(function () use ($report, $user, $data, $permissions): ReportSessionMember {
            $before = ReportSessionMember::query()->whereBelongsTo($report, 'report')->whereBelongsTo($user)->first()?->toArray();
            $member = ReportSessionMember::query()->updateOrCreate(
                ['report_session_id' => $report->id, 'user_id' => $user->id],
                ['role' => $data['role']] + $permissions,
            );
            AuditLog::record('MEMBER_UPSERTED', $member, $before, $member->fresh()->toArray(), $report->id);

            return $member->load('user');
        });
    }

    public function slideUpdate(UpdateSlideConfigRequest $request, ReportSession $report): RedirectResponse
    {
        $config = $report->slideConfig()->firstOrCreate();
        $settings = $config->settings ?? [];
        $oldBackgroundPath = $settings['background_path'] ?? null;

        if ($request->boolean('remove_background')) {
            if (is_string($oldBackgroundPath)) {
                Storage::disk('local')->delete($oldBackgroundPath);
            }
            unset($settings['background_path']);
        } elseif ($request->hasFile('background_image')) {
            $newBackgroundPath = $request->file('background_image')->store('slide-backgrounds/'.$report->id, 'local');
            if (is_string($oldBackgroundPath)) {
                Storage::disk('local')->delete($oldBackgroundPath);
            }
            $settings['background_path'] = $newBackgroundPath;
        }

        $settings['background_fit'] = 'cover';
        $settings['background_position'] = 'center';
        $settings['background_opacity'] = $request->integer('background_opacity', 14);
        $plainToken = $request->boolean('rotate_token') || blank($config->public_token)
            ? bin2hex(random_bytes(24))
            : null;

        DB::transaction(function () use ($request, $report, $config, $settings, $plainToken): void {
            $before = $config->toArray();
            $config->update($request->safe()->only(['starts_on', 'ends_on', 'duration_seconds', 'refresh_seconds']) + [
                'enabled' => $request->boolean('enabled'),
                'show_latest_transactions' => $request->boolean('show_latest_transactions'),
                'settings' => $settings,
            ] + ($plainToken !== null ? [
                'token_hash' => hash('sha256', $plainToken),
                'public_token' => $plainToken,
            ] : []));
            AuditLog::record('SLIDE_UPDATED', $config, $before, $config->fresh()->toArray(), $report->id);
        });

        return back()->with('success', 'Pengaturan slide disimpan.')->with('slide_token', $plainToken);
    }
}
