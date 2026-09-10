<?php

namespace Tests\Feature\Admin;

use App\Models\ApplicationSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApplicationSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_only_super_admin_can_open_and_update_application_settings(): void
    {
        $regularUser = User::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $this->actingAs($regularUser)->get(route('admin.settings.index'))->assertForbidden();

        $this->actingAs($superAdmin)->get(route('admin.settings.index'))
            ->assertSee('Konfigurasi Aplikasi')
            ->assertSee('data-application-settings-tabs', false)
            ->assertSee('data-settings-panel="users"', false)
            ->assertSee('data-settings-panel="identity"', false)
            ->assertSee('data-settings-panel="regional"', false)
            ->assertSee('data-settings-panel="security"', false)
            ->assertSee('data-settings-panel="uploads"', false)
            ->assertSee('role="tablist"', false)
            ->assertSee('aria-selected="true"', false)
            ->assertSee('data-ajax-user-form', false)
            ->assertSee('data-default-password-toggle', false)
            ->assertSee('Gunakan password awal aplikasi');
        $this->actingAs($superAdmin)->put(route('admin.settings.update'), [
            'app_name' => 'Keuangan Bersama', 'short_name' => 'KB', 'tagline' => 'Terbuka dan rapi',
            'primary_color' => '#12372A', 'accent_color' => '#D6A84B', 'locale' => 'id',
            'timezone' => 'Asia/Jakarta', 'currency' => 'IDR', 'date_format' => 'd/m/Y',
            'session_lifetime' => 60, 'login_max_attempts' => 5, 'password_min_length' => 6,
            'attachment_max_mb' => 5, 'identity_max_mb' => 2, '_settings_tab' => 'security',
        ])->assertRedirect(route('admin.settings.index').'#security')
            ->assertSessionHas('success', 'Konfigurasi Aplikasi disimpan.');

        $this->assertSame('Keuangan Bersama', ApplicationSetting::query()->where('key', 'app_name')->sole()->value);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $superAdmin->id, 'action' => 'APPLICATION_SETTINGS_UPDATED']);
    }

    public function test_last_active_super_admin_cannot_be_disabled(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->put(route('admin.users.update', $superAdmin), [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'active' => 0,
            'is_super_admin' => 1,
        ])->assertSessionHasErrors(['active' => 'Super Admin aktif terakhir tidak dapat dinonaktifkan atau diturunkan.']);

        $this->assertTrue($superAdmin->fresh()->active);
    }

    public function test_super_admin_resets_user_to_configured_initial_password_without_email(): void
    {
        config(['finance.initial_user_password' => 'pnmadiun@']);
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create(['must_change_password' => false]);

        $this->actingAs($superAdmin)->post(route('admin.users.password-reset', $user))
            ->assertSessionHas('success', 'Kata sandi dikembalikan ke password awal. Pengguna wajib menggantinya setelah login.');

        $user->refresh();
        $this->assertTrue(Hash::check('pnmadiun@', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'PASSWORD_RESET_TO_INITIAL_BY_ADMIN',
            'target_id' => (string) $user->id,
        ]);
    }

    public function test_regular_user_cannot_reset_another_users_password(): void
    {
        config(['finance.initial_user_password' => 'pnmadiun@']);
        $regularUser = User::factory()->create();
        $target = User::factory()->create(['password' => Hash::make('Tetap123')]);

        $this->actingAs($regularUser)->post(route('admin.users.password-reset', $target))
            ->assertForbidden();

        $this->assertTrue(Hash::check('Tetap123', $target->fresh()->password));
    }

    public function test_ajax_user_creation_returns_indonesian_password_validation_errors(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->postJson(route('admin.users.store'), [
            'name' => 'Operator Baru',
            'email' => 'operator-baru@example.test',
            'password' => 'abcdef',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'Kata sandi wajib memiliki setidaknya satu angka.');

        $this->assertDatabaseMissing('users', ['email' => 'operator-baru@example.test']);
    }

    public function test_ajax_user_creation_can_use_configured_initial_password(): void
    {
        config(['finance.initial_user_password' => 'pnmadiun@']);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->postJson(route('admin.users.store'), [
            'name' => 'Operator Awal',
            'email' => 'operator-awal@example.test',
            'use_default_password' => true,
            'password' => 'nilai-ini-diabaikan',
        ])->assertCreated()
            ->assertJsonPath('message', 'Pengguna dibuat dan wajib mengganti kata sandi saat login pertama.')
            ->assertJsonPath('redirect_url', route('admin.settings.index').'#users');

        $user = User::query()->where('email', 'operator-awal@example.test')->sole();
        $this->assertTrue(Hash::check('pnmadiun@', $user->password));
        $this->assertTrue($user->must_change_password);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $superAdmin->id,
            'action' => 'USER_CREATED',
            'target_id' => (string) $user->id,
        ]);
    }

    public function test_default_password_user_creation_fails_when_server_configuration_is_invalid(): void
    {
        config(['finance.initial_user_password' => null]);
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)->postJson(route('admin.users.store'), [
            'name' => 'Operator Tanpa Password',
            'email' => 'operator-tanpa-password@example.test',
            'use_default_password' => true,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['password'])
            ->assertJsonPath('errors.password.0', 'INITIAL_USER_PASSWORD pada .env wajib diisi minimal 6 karakter.');

        $this->assertDatabaseMissing('users', ['email' => 'operator-tanpa-password@example.test']);
    }
}
