<?php

namespace App\Http\Requests;

use App\Models\ReportSession;
use Illuminate\Foundation\Http\FormRequest;

class UpdateReportRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $openingBalance = $this->input('opening_balance');

        if (is_string($openingBalance) && preg_match('/^-?\d+(?:\.\d{3})*$/', trim($openingBalance)) === 1) {
            $this->merge(['opening_balance' => str_replace('.', '', trim($openingBalance))]);
        }
    }

    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportSession && ($this->user()?->can('update', $report) ?? false);
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        /** @var ReportSession $report */
        $report = $this->route('report');
        $openingBalanceChanged = $this->integer('opening_balance') !== $report->opening_balance;

        return [
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'opening_balance' => ['required', 'integer'],
            'opening_balance_reason' => [$openingBalanceChanged ? 'required' : 'nullable', 'string', 'min:5', 'max:500'],
            'status' => ['required', 'in:ACTIVE,CLOSED,ARCHIVED'],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }
}
