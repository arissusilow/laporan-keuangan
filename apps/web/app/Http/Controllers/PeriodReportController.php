<?php

namespace App\Http\Controllers;

use App\Jobs\GeneratePdfExport;
use App\Models\AuditLog;
use App\Models\PdfExport;
use App\Models\ReportSession;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PeriodReportController extends Controller
{
    public function show(Request $request, ReportSession $report, BalanceService $balances): View
    {
        Gate::forUser($request->user())->authorize('view', $report);
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'type' => ['nullable', 'in:IN,OUT'], 'category' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')->where('report_session_id', $report->id)]]);
        $from = $data['from'] ?? now()->startOfMonth()->toDateString();
        $to = $data['to'] ?? now()->endOfMonth()->toDateString();
        $summary = $balances->period($report, $from, $to, $data);
        $transactions = $report->transactions()->where('status', 'ACTIVE')->with('category')->whereBetween('transaction_date', [$from, $to])->when($data['type'] ?? null, fn ($q, $v) => $q->where('type', $v))->when($data['category'] ?? null, fn ($q, $v) => $q->where('expense_category_id', $v))->orderBy('transaction_date')->paginate(50)->withQueryString();
        $exports = PdfExport::where('report_session_id', $report->id)->where('requested_by', $request->user()->id)->latest()->limit(10)->get();
        $categories = $report->categories()->orderBy('name')->get();

        return view('period.show', compact('report', 'from', 'to', 'summary', 'transactions', 'exports', 'categories', 'data'));
    }

    public function export(Request $request, ReportSession $report): RedirectResponse
    {
        Gate::forUser($request->user())->authorize('create', [PdfExport::class, $report]);
        $data = $request->validate(['from' => 'required|date', 'to' => 'required|date|after_or_equal:from', 'type' => 'nullable|in:IN,OUT', 'category' => ['nullable', Rule::exists('expense_categories', 'id')->where('report_session_id', $report->id)]]);
        $export = PdfExport::create(['report_session_id' => $report->id, 'requested_by' => $request->user()->id, 'starts_on' => $data['from'], 'ends_on' => $data['to'], 'transaction_type' => $data['type'] ?? null, 'expense_category_id' => $data['category'] ?? null, 'status' => 'PENDING']);
        GeneratePdfExport::dispatch($export->id);
        AuditLog::record('PDF_QUEUED', $export, null, $export->toArray(), $report->id);

        return back()->with('success', 'PDF masuk antrean. Muat ulang halaman untuk melihat status.');
    }

    public function download(Request $request, PdfExport $export): StreamedResponse
    {
        Gate::forUser($request->user())->authorize('view', $export);
        $report = $export->report;
        abort_unless($export->status === 'READY' && $export->path, 404);
        AuditLog::record('PDF_DOWNLOADED', $export, null, null, $report->id);

        return Storage::disk('local')->download($export->path, 'laporan-'.$report->id.'-'.$export->starts_on->format('Ymd').'.pdf');
    }
}
