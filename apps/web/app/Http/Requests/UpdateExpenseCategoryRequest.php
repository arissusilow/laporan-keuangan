<?php

namespace App\Http\Requests;

use App\Models\ExpenseCategory;
use App\Models\ReportSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('report');
        $category = $this->route('category');

        return $report instanceof ReportSession
            && $category instanceof ExpenseCategory
            && $category->report_session_id === $report->id
            && ($this->user()?->can('update', $category) ?? false);
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var ReportSession $report */
        $report = $this->route('report');
        /** @var ExpenseCategory $category */
        $category = $this->route('category');
        $type = strtoupper((string) $this->input('type'));

        return [
            'type' => ['required', Rule::in(['IN', 'OUT'])],
            'name' => ['required', 'string', 'max:100', Rule::unique('expense_categories')->where(fn ($query) => $query->where('report_session_id', $report->id)->where('type', $type))->ignore($category)],
            'color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'is_default' => ['nullable', 'boolean'],
        ];
    }
}
