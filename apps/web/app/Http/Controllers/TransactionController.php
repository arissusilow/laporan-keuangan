<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertTransactionRequest;
use App\Models\Attachment;
use App\Models\AuditLog;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Services\BalanceService;
use App\Services\TransactionBrowserService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request, ReportSession $report, TransactionBrowserService $browser, BalanceService $balances): View
    {
        Gate::authorize('view', $report);
        $filters = $request->validate([
            'view' => ['nullable', 'in:day,week,month,year'],
            'period' => ['nullable', 'date'],
            'type' => ['nullable', 'in:IN,OUT'],
            'category' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:asc,desc'],
        ]);

        $periodMode = $filters['view'] ?? 'day';
        $periodAnchor = CarbonImmutable::parse($filters['period'] ?? now()->toDateString());
        [$periodStart, $periodEnd, $periodLabel, $previousPeriod, $nextPeriod] = match ($periodMode) {
            'day', 'week' => [
                $periodAnchor->startOfMonth(),
                $periodAnchor->endOfMonth(),
                $periodAnchor->locale('id')->translatedFormat('F Y'),
                $periodAnchor->startOfMonth()->subMonth(),
                $periodAnchor->startOfMonth()->addMonth(),
            ],
            'month' => [
                $periodAnchor->startOfYear(),
                $periodAnchor->endOfYear(),
                $periodAnchor->format('Y'),
                $periodAnchor->startOfYear()->subYear(),
                $periodAnchor->startOfYear()->addYear(),
            ],
            default => [null, null, 'Total', null, null],
        };
        $periodQuery = $request->only(['type', 'category', 'q', 'sort']);
        $periodUrl = fn (?CarbonImmutable $period): ?string => $period ? route('transactions.index', $report).'?'.http_build_query(array_filter($periodQuery + [
            'view' => $periodMode,
            'period' => $period->toDateString(),
        ], fn ($value): bool => $value !== null && $value !== '')) : null;
        $periodTabs = collect([
            'day' => 'Harian',
            'week' => 'Mingguan',
            'month' => 'Bulanan',
            'year' => 'Tahunan',
        ])->mapWithKeys(fn (string $label, string $mode): array => [$mode => [
            'label' => $label,
            'url' => route('transactions.index', $report).'?'.http_build_query(array_filter($periodQuery + [
                'view' => $mode,
                'period' => $periodAnchor->toDateString(),
            ], fn ($value): bool => $value !== null && $value !== '')),
        ]]);

        $result = $browser->build($report, $filters, $periodMode, $periodAnchor, max(1, $request->integer('page', 1)), $request->url(), $request->query());
        $breakdown = $result['breakdown'];
        $totals = $result['totals'];
        $periodBalance = $balances->balance($report, $periodEnd?->toDateString());
        $breakdownTransactionCount = $result['transaction_count'];
        $categories = $report->categories()->orderBy('sort_order')->get();

        return view('transactions.index', compact(
            'report',
            'breakdown',
            'totals',
            'periodBalance',
            'categories',
            'filters',
            'periodMode',
            'periodAnchor',
            'periodLabel',
            'periodTabs',
            'breakdownTransactionCount',
        ) + [
            'previousPeriodUrl' => $periodUrl($previousPeriod),
            'nextPeriodUrl' => $periodUrl($nextPeriod),
        ]);
    }

    public function create(ReportSession $report, ?string $type = null): View
    {
        abort_unless($report->isWritable(), 409);
        abort_unless($type === null || in_array($type, ['in', 'out'], true), 404);

        $allowedTypes = collect(['OUT', 'IN'])
            ->filter(fn (string $kind): bool => Gate::allows('create', [FinancialTransaction::class, $report, $kind]))
            ->values();
        abort_if($allowedTypes->isEmpty(), 403);

        $kind = $type === null
            ? ($allowedTypes->contains('IN') ? 'IN' : $allowedTypes->first())
            : strtoupper($type);
        Gate::authorize('create', [FinancialTransaction::class, $report, $kind]);
        $categories = $report->categories()->whereIn('type', $allowedTypes)->where('active', true)->orderBy('sort_order')->get();

        return view('transactions.form', [
            'report' => $report,
            'type' => $kind,
            'transaction' => null,
            'categories' => $categories,
            'allowedTypes' => $allowedTypes->all(),
        ]);
    }

    public function store(UpsertTransactionRequest $request, ReportSession $report, string $type): RedirectResponse
    {
        abort_unless(in_array($type, ['in', 'out'], true), 404);
        abort_unless($report->isWritable(), 409);
        $kind = strtoupper($type);
        Gate::authorize('create', [FinancialTransaction::class, $report, $kind]);
        $data = $request->safe()->except(['attachment', 'new_category_name']);
        $newCategoryName = $request->validated('new_category_name');

        $transaction = DB::transaction(function () use ($report, $request, $data, $kind, $newCategoryName): FinancialTransaction {
            ReportSession::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            if (filled($newCategoryName)) {
                $category = $report->categories()
                    ->where('type', $kind)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($newCategoryName)])
                    ->first();
                if ($category && ! $category->active) {
                    throw ValidationException::withMessages([
                        'new_category_name' => 'Kategori tersebut sudah ada tetapi sedang nonaktif. Hubungi Admin Laporan untuk mengaktifkannya.',
                    ]);
                }
                if (! $category) {
                    $category = $report->categories()->create([
                        'type' => $kind,
                        'name' => $newCategoryName,
                        'color' => $kind === 'IN' ? '#176B45' : '#B42318',
                        'sort_order' => ((int) $report->categories()->where('type', $kind)->max('sort_order')) + 1,
                        'active' => true,
                        'is_default' => false,
                    ]);
                    AuditLog::record('CATEGORY_CREATED_BY_OPERATOR', $category, null, $category->toArray(), $report->id);
                }
                $data['expense_category_id'] = $category->id;
            }
            $sequence = (int) $report->transactions()->max('id') + 1;
            $transaction = FinancialTransaction::query()->create($data + [
                'report_session_id' => $report->id,
                'created_by' => $request->user()->id,
                'type' => $kind,
                'number' => sprintf('TRX-%s-%06d', now()->format('Ym'), $sequence),
                'status' => 'ACTIVE',
            ]);

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $path = $file->store('attachments/'.$report->id, 'local');
                Attachment::query()->create([
                    'report_session_id' => $report->id,
                    'transaction_id' => $transaction->id,
                    'disk' => 'local',
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => (string) $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
            }

            AuditLog::record('TRANSACTION_CREATED', $transaction, null, $transaction->toArray(), $report->id);

            return $transaction;
        });

        return redirect()->route('reports.dashboard', $report)->with('success', ($transaction->type === 'IN' ? 'Uang Masuk' : 'Uang Keluar').' berhasil disimpan.');
    }

    public function edit(ReportSession $report, FinancialTransaction $transaction): View
    {
        $this->guardTransactionScope($report, $transaction);
        abort_unless($report->isWritable() && $transaction->status === 'ACTIVE', 409);
        Gate::authorize('update', $transaction);

        return view('transactions.form', [
            'report' => $report,
            'type' => $transaction->type,
            'transaction' => $transaction,
            'categories' => $report->categories()->where('type', $transaction->type)->orderBy('sort_order')->get(),
            'allowedTypes' => [$transaction->type],
        ]);
    }

    public function show(ReportSession $report, FinancialTransaction $transaction): View
    {
        $this->guardTransactionScope($report, $transaction);
        Gate::authorize('view', $transaction);
        $transaction->load(['category', 'creator', 'updater', 'canceller', 'attachments']);

        return view('transactions.show', compact('report', 'transaction'));
    }

    public function update(UpsertTransactionRequest $request, ReportSession $report, FinancialTransaction $transaction): RedirectResponse
    {
        $this->guardTransactionScope($report, $transaction);
        abort_unless($report->isWritable() && $transaction->status === 'ACTIVE', 409);
        Gate::authorize('update', $transaction);

        DB::transaction(function () use ($request, $report, $transaction): void {
            $before = $transaction->toArray();
            $transaction->update($request->safe()->except('attachment') + ['updated_by' => $request->user()->id]);
            AuditLog::record('TRANSACTION_UPDATED', $transaction, $before, $transaction->fresh()->toArray(), $report->id);
        });

        return redirect()->route('transactions.index', $report)->with('success', 'Transaksi diperbarui.');
    }

    public function cancel(Request $request, ReportSession $report, FinancialTransaction $transaction): RedirectResponse
    {
        $this->guardTransactionScope($report, $transaction);
        abort_unless($report->isWritable() && $transaction->status === 'ACTIVE', 409);
        Gate::authorize('cancel', $transaction);
        $data = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:500']]);

        DB::transaction(function () use ($request, $report, $transaction, $data): void {
            $before = $transaction->toArray();
            $transaction->update([
                'status' => 'CANCELLED',
                'cancellation_reason' => $data['reason'],
                'cancelled_at' => now(),
                'cancelled_by' => $request->user()->id,
            ]);
            AuditLog::record('TRANSACTION_CANCELLED', $transaction, $before, $transaction->fresh()->toArray(), $report->id);
        });

        return back()->with('success', 'Transaksi dibatalkan.');
    }

    private function guardTransactionScope(ReportSession $report, FinancialTransaction $transaction): void
    {
        abort_unless($transaction->report_session_id === $report->id, 404);
    }
}
