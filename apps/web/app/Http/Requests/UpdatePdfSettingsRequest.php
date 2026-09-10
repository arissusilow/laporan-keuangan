<?php

namespace App\Http\Requests;

use App\Models\ReportSession;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePdfSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportSession && ($this->user()?->can('update', $report) ?? false);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:160'],
            'footer' => ['nullable', 'string', 'max:200'],
            'margin_mm' => ['required', 'integer', 'min:8', 'max:25'],
        ];
    }
}
