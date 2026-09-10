<?php

namespace App\Services;

use App\Models\ApplicationSetting;
use Throwable;

class ApplicationSettings
{
    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'app_name' => 'Laporan Keuangan',
        'short_name' => 'LK',
        'tagline' => 'Catatan sederhana dan transparan',
        'primary_color' => '#12372A',
        'accent_color' => '#D6A84B',
        'locale' => 'id',
        'timezone' => 'Asia/Jakarta',
        'currency' => 'IDR',
        'date_format' => 'd/m/Y',
        'session_lifetime' => 60,
        'login_max_attempts' => 5,
        'password_min_length' => 6,
        'attachment_max_mb' => 5,
        'identity_max_mb' => 2,
        'backup_frequency' => 'DAILY',
        'backup_time' => '02:00',
        'backup_retention_daily' => 14,
    ];

    /** @var array<string, string> */
    public const GROUPS = [
        'app_name' => 'identity', 'short_name' => 'identity', 'tagline' => 'identity',
        'primary_color' => 'identity', 'accent_color' => 'identity', 'logo_path' => 'identity',
        'locale' => 'regional', 'timezone' => 'regional', 'currency' => 'regional', 'date_format' => 'regional',
        'session_lifetime' => 'security', 'login_max_attempts' => 'security', 'password_min_length' => 'security',
        'attachment_max_mb' => 'uploads', 'identity_max_mb' => 'uploads',
        'backup_frequency' => 'backup', 'backup_time' => 'backup', 'backup_retention_daily' => 'backup',
    ];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    /** @return array<string, mixed> */
    public function all(): array
    {
        if ($this->values !== null) {
            return $this->values;
        }

        try {
            $stored = ApplicationSetting::query()->pluck('value', 'key')->all();
        } catch (Throwable) {
            return self::DEFAULTS;
        }

        return $this->values = array_replace(self::DEFAULTS, $stored);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function integer(string $key, int $default): int
    {
        return (int) $this->get($key, $default);
    }

    public function forget(): void
    {
        $this->values = null;
    }
}
