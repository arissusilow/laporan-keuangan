<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_unauthenticated_request_redirects_to_login(): void
    {
        $this->get('/laporan')->assertRedirectToRoute('login');
    }

    public function test_login_form_has_password_visibility_and_keyboard_submit_controls(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('data-password-toggle', false)
            ->assertSee('data-login-form', false)
            ->assertSee('enterkeyhint="go"', false)
            ->assertSee('data-login-submit', false);
    }

    public function test_active_user_can_login_with_a_real_session(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Rahasia123456')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Rahasia123456'])
            ->assertRedirectToRoute('reports.index');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'LOGIN']);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $user = User::factory()->inactive()->create(['password' => Hash::make('Rahasia123456')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Rahasia123456'])
            ->assertSessionHasErrors(['email' => 'Email atau kata sandi tidak sesuai.']);

        $this->assertGuest();
    }

    public function test_login_is_rate_limited_after_five_failed_attempts(): void
    {
        $payload = ['email' => 'rate-limit@example.test', 'password' => 'salah'];
        foreach (range(1, 5) as $attempt) {
            $this->post('/login', $payload)->assertSessionHasErrors('email');
        }

        $response = $this->post('/login', $payload);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', session('errors')->first('email'));
    }

    public function test_first_login_requires_password_change_before_accessing_reports(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => Hash::make('Rahasia123456')]);

        $this->post('/login', ['email' => $user->email, 'password' => 'Rahasia123456'])
            ->assertRedirectToRoute('password.first');
        $this->get('/laporan')->assertRedirectToRoute('password.first');
    }

    public function test_initial_password_change_form_uses_one_visibility_control_for_new_password_and_confirmation(): void
    {
        $user = User::factory()->mustChangePassword()->create();

        $this->actingAs($user)->get(route('password.first'))
            ->assertOk()
            ->assertSee('aria-controls="current_password"', false)
            ->assertSee('aria-controls="password password_confirmation"', false)
            ->assertDontSee('aria-controls="password_confirmation"', false)
            ->assertSee('Tampilkan kata sandi baru dan konfirmasi');
    }

    public function test_password_reset_request_has_generic_response_and_sends_notification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post('/lupa-kata-sandi', ['email' => $user->email])
            ->assertSessionHas('status', 'Jika email terdaftar, tautan reset telah dikirim.');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_valid_password_reset_token_changes_the_password_and_is_audited(): void
    {
        $user = User::factory()->mustChangePassword()->create();
        $token = Password::createToken($user);

        $this->post('/reset-kata-sandi', [
            'token' => $token,
            'email' => $user->email,
            'password' => 'RahasiaBaru123456',
            'password_confirmation' => 'RahasiaBaru123456',
        ])->assertRedirectToRoute('login');

        $user->refresh();
        $this->assertTrue(Hash::check('RahasiaBaru123456', $user->password));
        $this->assertFalse($user->must_change_password);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $user->id, 'action' => 'PASSWORD_RESET']);
    }

    public function test_six_character_password_is_accepted_for_first_change(): void
    {
        $user = User::factory()->mustChangePassword()->create(['password' => Hash::make('Lama123')]);

        $this->actingAs($user)->put(route('password.first.update'), [
            'current_password' => 'Lama123',
            'password' => 'Baru12',
            'password_confirmation' => 'Baru12',
        ])->assertRedirectToRoute('reports.index');

        $this->assertTrue(Hash::check('Baru12', $user->fresh()->password));
    }

    public function test_deactivated_authenticated_user_is_logged_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)->get('/laporan')->assertRedirectToRoute('login');

        $this->assertGuest();
    }
}
