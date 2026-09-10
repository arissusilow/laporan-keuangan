<?php

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\PdfExport;
use App\Models\ReportSession;
use App\Services\BalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GeneratePdfExport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 180;

    public function __construct(public int $exportId) {}

    public function handle(BalanceService $balances): void
    {
        $export = PdfExport::findOrFail($this->exportId);
        $export->update(['status' => 'PROCESSING']);
        $report = ReportSession::findOrFail($export->report_session_id);
        $from = $export->starts_on->toDateString();
        $to = $export->ends_on->toDateString();
        $filters = ['type' => $export->transaction_type, 'category' => $export->expense_category_id];
        $summary = $balances->period($report, $from, $to, $filters);
        $transactions = $report->transactions()->where('status', 'ACTIVE')->with('category')->whereBetween('transaction_date', [$from, $to])->when($export->transaction_type, fn ($query, $type) => $query->where('type', $type))->when($export->expense_category_id, fn ($query, $category) => $query->where('expense_category_id', $category))->orderBy('transaction_date')->orderBy('id')->get();
        $contents = Pdf::loadView('pdf.report', compact('report', 'from', 'to', 'summary', 'transactions'))->setPaper('a4', 'portrait')->output();
        $path = 'pdf/'.$report->id.'/'.$export->id.'.pdf';
        Storage::put($path, $contents);
        $export->update(['status' => 'READY', 'path' => $path]);
        AuditLog::recordFor($export->requested_by, 'PDF_READY', $export, null, ['path' => $path], $report->id);
    }

    public function failed(Throwable $e): void
    {
        $export = PdfExport::find($this->exportId);
        $export?->update(['status' => 'FAILED', 'error' => 'Pembuatan PDF gagal.']);
        if ($export) {
            AuditLog::recordFor($export->requested_by, 'PDF_FAILED', $export, null, ['message' => 'Pembuatan PDF gagal.'], $export->report_session_id);
        }
    }
}
