<?php

namespace App\Http\Requests;

use App\Models\ApplicationSetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateApplicationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', ApplicationSetting::class) ?? false;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'app_name' => ['required', 'string', 'max:80'],
            'short_name' => ['required', 'string', 'max:6'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'accent_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'locale' => ['required', 'in:id'],
            'timezone' => ['required', 'timezone'],
            'currency' => ['required', 'in:IDR'],
            'date_format' => ['required', 'in:d/m/Y,Y-m-d'],
            'session_lifetime' => ['required', 'integer', 'min:15', 'max:1440'],
            'login_max_attempts' => ['required', 'integer', 'min:3', 'max:20'],
            'password_min_length' => ['required', 'integer', 'min:6', 'max:128'],
            'attachment_max_mb' => ['required', 'integer', 'min:1', 'max:20'],
            'identity_max_mb' => ['required', 'integer', 'min:1', 'max:10'],
            'app_logo' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max($this->integer('identity_max_mb', 2).'mb')],
        ];
    }
}
