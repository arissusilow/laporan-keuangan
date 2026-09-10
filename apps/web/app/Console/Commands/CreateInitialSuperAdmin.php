<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateInitialSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin {--email=} {--name=}';

    protected $description = 'Membuat Super Admin awal secara aman dan idempoten';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) ($this->option('email') ?: env('ADMIN_EMAIL') ?: $this->ask('Email Super Admin'))));
        $name = trim((string) ($this->option('name') ?: env('ADMIN_NAME') ?: $this->ask('Nama Super Admin', 'Super Admin')));

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Email tidak valid.');

            return self::FAILURE;
        }

        $existing = User::query()->where('email', $email)->first();
        if ($existing !== null) {
            if (! $existing->is_super_admin) {
                $this->error('Identitas sudah dipakai akun non-Super Admin; tidak ada perubahan dilakukan.');

                return self::FAILURE;
            }

            $this->info('Super Admin dengan identitas tersebut sudah ada; tidak ada akun baru dibuat.');

            return self::SUCCESS;
        }

        $password = env('ADMIN_PASSWORD') ?: $this->secret('Kata sandi awal (minimal 6 karakter, huruf dan angka)');
        if (! is_string($password) || strlen($password) < 6 || ! preg_match('/[A-Za-z]/', $password) || ! preg_match('/[0-9]/', $password)) {
            $this->error('Kata sandi minimal 6 karakter serta harus memuat huruf dan angka.');

            return self::FAILURE;
        }

        User::query()->create([
            'name' => $name !== '' ? $name : 'Super Admin',
            'email' => $email,
            'password' => Hash::make($password),
            'is_super_admin' => true,
            'active' => true,
            'must_change_password' => true,
        ]);

        $this->info('Super Admin dibuat. Kata sandi tidak ditampilkan dan wajib diganti saat login pertama.');

        return self::SUCCESS;
    }
}
