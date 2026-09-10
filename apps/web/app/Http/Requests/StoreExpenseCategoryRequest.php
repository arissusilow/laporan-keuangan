<?php

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use App\Models\ReportSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');

        return $report instanceof ReportSession
            && ($this->user()?->can('create', [ExpenseCategory::class, $report]) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var ReportSession $report */
        $report = $this->route('report');

        $type = strtoupper((string) $this->input('type'));

        return [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_categories')->where(fn ($query) => $query->where('report_session_id', $report->id)->where('type', $type))],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
