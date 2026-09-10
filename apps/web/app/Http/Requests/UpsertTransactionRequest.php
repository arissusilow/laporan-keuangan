<?php

namespace App\Http\Requests;

use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Services\ApplicationSettings;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;

class UpsertTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $amount = $this->input('amount');
        $newCategoryName = $this->input('new_category_name');

        if (is_string($newCategoryName)) {
            $this->merge(['new_category_name' => trim($newCategoryName) ?: null]);
        }

        if (! is_string($amount)) {
            return;
        }

        $amount = trim($amount);
        if (preg_match('/^\d+(?:\.\d{3})*$/', $amount) === 1) {
            $this->merge(['amount' => str_replace('.', '', $amount)]);
        }
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var ReportSession $report */
        $report = $this->route('report');
        $transaction = $this->route('transaction');
        $type = $transaction instanceof FinancialTransaction
            ? $transaction->type
            : strtoupper((string) $this->route('type'));

        return [
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:1000'],
            'expense_category_id' => [
                'nullable',
                'required_without:new_category_name',
                'integer',
                Rule::exists('expense_categories', 'id')->where(fn ($query) => $query
                    ->where('report_session_id', $report->id)
                    ->where('type', $type)
                    ->where('active', true)),
            ],
            'new_category_name' => ['nullable', 'required_without:expense_category_id', 'string', 'max:100'],
            'attachment' => ['nullable', File::types(['jpg', 'jpeg', 'png', 'pdf'])->max(app(ApplicationSettings::class)->integer('attachment_max_mb', 5).'mb')],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'amount.required' => 'Jumlah wajib diisi.',
            'amount.integer' => 'Jumlah harus berupa angka Rupiah yang valid.',
            'amount.min' => 'Nominal harus lebih dari nol.',
            'expense_category_id.required_without' => 'Pilih kategori atau tulis kategori baru.',
            'expense_category_id.exists' => 'Kategori harus aktif, sesuai jenis transaksi, dan berasal dari Laporan ini.',
            'new_category_name.required_without' => 'Pilih kategori atau tulis kategori baru.',
            'new_category_name.max' => 'Nama kategori baru maksimal 100 karakter.',
        ];
    }
}
