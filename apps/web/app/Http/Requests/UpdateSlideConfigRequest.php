<?php

namespace App\Http\Requests;

use App\Models\ReportSession;
use App\Models\SlideConfig;
use App\Services\ApplicationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class UpdateSlideConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportSession
            && ($this->user()?->can('update', [SlideConfig::class, $report]) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'duration_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'refresh_seconds' => ['required', 'integer', 'min:15', 'max:3600'],
            'enabled' => ['nullable', 'boolean'],
            'show_latest_transactions' => ['nullable', 'boolean'],
            'rotate_token' => ['nullable', 'boolean'],
            'background_opacity' => ['nullable', 'integer', 'min:5', 'max:30'],
            'background_image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(app(ApplicationSettings::class)->integer('identity_max_mb', 2).'mb')],
            'remove_background' => ['nullable', 'boolean'],
        ];
    }
}
