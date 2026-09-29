<?php

namespace App\Http\Requests;

use App\Models\ReportSession;
use App\Models\SlideConfig;
use App\Services\ApplicationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpdateSlideConfigRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $alias = Str::of((string) $this->input('public_alias'))->trim()->lower()->ltrim('/');

        $this->merge([
            'public_alias' => $alias->isEmpty() ? null : $alias->toString(),
        ]);
    }

    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportSession
            && ($this->user()?->can('update', [SlideConfig::class, $report]) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $slideConfigId = $this->route('report') instanceof ReportSession
            ? $this->route('report')->slideConfig()->value('id')
            : null;

        return [
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'duration_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'refresh_seconds' => ['required', 'integer', 'min:15', 'max:3600'],
            'enabled' => ['nullable', 'boolean'],
            'show_latest_transactions' => ['nullable', 'boolean'],
            'rotate_token' => ['nullable', 'boolean'],
            'public_alias' => [
                'nullable',
                'string',
                'max:80',
                'regex:/\Aslide_[a-z0-9_-]+\z/',
                Rule::unique('slide_configs', 'public_alias')->ignore($slideConfigId),
            ],
            'background_opacity' => ['nullable', 'integer', 'min:5', 'max:30'],
            'background_image' => ['nullable', File::image()->types(['jpg', 'jpeg', 'png', 'webp'])->max(app(ApplicationSettings::class)->integer('identity_max_mb', 2).'mb')],
            'remove_background' => ['nullable', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'public_alias.regex' => 'Alias slide harus diawali slide_ dan hanya boleh berisi huruf kecil, angka, garis bawah, atau tanda hubung.',
            'public_alias.unique' => 'Alias slide sudah digunakan oleh laporan lain.',
        ];
    }
}
