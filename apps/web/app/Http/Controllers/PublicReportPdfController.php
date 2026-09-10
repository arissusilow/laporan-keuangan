<?php

namespace App\Http\Controllers;

use App\Models\ReportSession;
use App\Models\SlideConfig;
use App\Services\BalanceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class PublicReportPdfController extends Controller
{
    public function __invoke(string $token, BalanceService $balances): Response
    {
        $config = SlideConfig::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('enabled', true)
            ->firstOrFail();
        $report = ReportSession::query()->findOrFail($config->report_session_id);
        $to = $report->ends_on?->isBefore(now()) ? $report->ends_on->toDateString() : now()->toDateString();
        $from = $report->starts_on?->toDateString()
            ?? $report->transactions()->where('status', 'ACTIVE')->min('transaction_date')
            ?? $to;

        if ($from > $to) {
            $from = $to;
        }

        $summary = $balances->period($report, $from, $to);
        $transactions = $report->transactions()
            ->where('status', 'ACTIVE')
            ->with('category')
            ->whereBetween('transaction_date', [$from, $to])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();
        $filename = Str::slug($report->name).'-lengkap-'.$to.'.pdf';

        return Pdf::loadView('pdf.report', compact('report', 'from', 'to', 'summary', 'transactions'))
            ->setPaper('a4', 'portrait')
            ->stream($filename);
    }
}
