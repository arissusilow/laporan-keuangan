<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateInitialSuperAdminTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_command_creates_one_super_admin_and_is_idempotent(): void
    {
        $arguments = ['--email' => 'owner@example.test', '--name' => 'Pemilik Sistem'];

        $this->artisan('app:create-super-admin', $arguments)
            ->expectsQuestion('Kata sandi awal (minimal 6 karakter, huruf dan angka)', 'Abc123')
            ->expectsOutputToContain('Kata sandi tidak ditampilkan')
            ->assertSuccessful();
        $this->artisan('app:create-super-admin', $arguments)
            ->expectsOutputToContain('tidak ada akun baru dibuat')
            ->assertSuccessful();

        $this->assertSame(1, User::query()->count());
        $user = User::query()->sole();
        $this->assertTrue($user->is_super_admin);
        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check('Abc123', $user->password));
    }

    public function test_command_does_not_escalate_an_existing_regular_user(): void
    {
        User::factory()->create(['email' => 'member@example.test', 'is_super_admin' => false]);

        $this->artisan('app:create-super-admin', ['--email' => 'member@example.test', '--name' => 'Anggota'])
            ->expectsOutputToContain('tidak ada perubahan dilakukan')
            ->assertFailed();

        $this->assertFalse(User::query()->where('email', 'member@example.test')->sole()->is_super_admin);
    }
}
