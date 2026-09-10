<?php

namespace App\Http\Requests;

use App\Models\ReportSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportMemberRequest extends FormRequest
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
            'email' => ['required', 'email', Rule::exists('users', 'email')->where('active', true)],
            'role' => ['required', 'in:ADMIN,OFFICER,VIEWER'],
            'can_add_in' => ['nullable', 'boolean'],
            'can_add_out' => ['nullable', 'boolean'],
            'can_edit_own' => ['nullable', 'boolean'],
            'can_edit_all' => ['nullable', 'boolean'],
            'can_cancel' => ['nullable', 'boolean'],
            'can_export_pdf' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
    }
}
