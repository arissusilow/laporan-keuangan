<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReportRequest;
use App\Http\Requests\UpdatePdfSettingsRequest;
use App\Http\Requests\UpdateReportRequest;
use App\Models\AuditLog;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ReportSession::class);
        $reports = $request->user()->is_super_admin
            ? ReportSession::query()->orderBy('name')->get()
            : $request->user()->reports()->orderBy('name')->get();

        return view('reports.index', compact('reports'));
    }

    public function dashboard(ReportSession $report, BalanceService $balances): View
    {
        abort_unless(Gate::allows('view', $report), 404);
        $totals = $balances->totals($report);
        $balance = $balances->balance($report);
        $month = $balances->totals($report, now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString());
        $latest = $report->transactions()->where('status', 'ACTIVE')->with('category')->latest('transaction_date')->latest('id')->limit(5)->get();
        $categories = $report->transactions()->where('transactions.status', 'ACTIVE')->where('transactions.type', 'OUT')->join('expense_categories', 'expense_categories.id', '=', 'transactions.expense_category_id')->selectRaw('expense_categories.name, expense_categories.color, SUM(transactions.amount) total')->groupBy('expense_categories.id', 'expense_categories.name', 'expense_categories.color')->orderByDesc('total')->limit(8)->get();

        return view('reports.dashboard', compact('report', 'totals', 'balance', 'month', 'latest', 'categories'));
    }

    public function create(): View
    {
        Gate::authorize('create', ReportSession::class);

        return view('reports.create');
    }

    public function store(StoreReportRequest $request): RedirectResponse
    {
        $report = DB::transaction(function () use ($request): ReportSession {
            $report = ReportSession::query()->create($request->safe()->only(['name', 'description', 'starts_on', 'ends_on', 'opening_balance', 'color']) + [
                'status' => 'ACTIVE',
                'currency' => 'IDR',
            ]);
            ReportSessionMember::query()->create([
                'report_session_id' => $report->id,
                'user_id' => $request->user()->id,
                'role' => 'ADMIN',
                'can_add_in' => true,
                'can_add_out' => true,
                'can_edit_own' => true,
                'can_edit_all' => true,
                'can_cancel' => true,
                'can_export_pdf' => true,
            ]);
            $report->slideConfig()->create();
            AuditLog::record('REPORT_CREATED', $report, null, $report->toArray(), $report->id);

            return $report;
        });

        return redirect()->route('reports.dashboard', $report)->with('success', 'Laporan berhasil dibuat.');
    }

    public function settings(ReportSession $report): View
    {
        Gate::authorize('update', $report);
        $report->load('slideConfig');
        $categories = $report->categories()->orderBy('sort_order')->get();
        $members = $report->members()->with('user')->get();
        $availableUsers = User::query()
            ->where('active', true)
            ->whereNotIn('id', $members->pluck('user_id'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
        $audits = $report->auditLogs()->latest()->limit(30)->get();
        $slidePublicUrl = $report->slideConfig?->enabled && filled($report->slideConfig->public_token)
            ? route('slides.show', $report->slideConfig->public_token)
            : null;

        return view('reports.settings', compact('report', 'categories', 'members', 'availableUsers', 'audits', 'slidePublicUrl'));
    }

    public function update(UpdateReportRequest $request, ReportSession $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report): void {
            $before = $report->toArray();
            $data = $request->safe()->only(['name', 'description', 'starts_on', 'ends_on', 'opening_balance', 'status', 'color']);
            $reason = $request->validated('opening_balance_reason');
            $report->update($data);
            AuditLog::record('REPORT_UPDATED', $report, $before, $report->fresh()->toArray() + ['opening_balance_reason' => $reason], $report->id);
        });

        return back()->with('success', 'Pengaturan Laporan disimpan.');
    }

    public function updatePdfSettings(UpdatePdfSettingsRequest $request, ReportSession $report): RedirectResponse
    {
        DB::transaction(function () use ($request, $report): void {
            $before = $report->pdf_settings;
            $settings = $request->validated();
            $report->update(['pdf_settings' => $settings]);
            AuditLog::record('PDF_SETTINGS_UPDATED', $report, $before, $settings, $report->id);
        });

        return redirect()->to(route('reports.settings', $report).'?tab=pdf')->with('success', 'Pengaturan PDF disimpan.');
    }
}
