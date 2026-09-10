<?php

namespace App\Http\Controllers;

use App\Models\ReportSession;
use App\Models\SlideConfig;
use App\Services\BalanceService;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SlideController extends Controller
{
    public function show(string $token, BalanceService $balances, QrCodeService $qrCodes): View
    {
        $config = SlideConfig::where('token_hash', hash('sha256', $token))->first();
        abort_unless($config && $config->enabled, 404);
        $report = ReportSession::findOrFail($config->report_session_id);
        $backgroundUrl = route('slides.background', $token);

        return $this->renderSlides($report, $config, $balances, $qrCodes, $backgroundUrl, $token);
    }

    public function preview(ReportSession $report, BalanceService $balances, QrCodeService $qrCodes): View
    {
        Gate::authorize('update', $report);
        $config = $report->slideConfig()->firstOrFail();
        $backgroundUrl = route('slides.preview.background', $report);

        return $this->renderSlides($report, $config, $balances, $qrCodes, $backgroundUrl, $config->public_token);
    }

    public function background(string $token): StreamedResponse
    {
        $config = SlideConfig::query()->where('token_hash', hash('sha256', $token))->firstOrFail();
        abort_unless($config->enabled, 404);

        return $this->backgroundResponse($config);
    }

    public function previewBackground(ReportSession $report): StreamedResponse
    {
        Gate::authorize('update', $report);

        return $this->backgroundResponse($report->slideConfig()->firstOrFail());
    }

    private function renderSlides(ReportSession $report, SlideConfig $config, BalanceService $balances, QrCodeService $qrCodes, string $backgroundUrl, ?string $token): View
    {
        $from = $config->starts_on?->toDateString() ?? now()->startOfYear()->toDateString();
        $to = $config->ends_on?->toDateString() ?? now()->toDateString();
        $summary = $balances->period($report, $from, $to);
        $currentMonthFrom = now()->startOfMonth()->toDateString();
        $currentMonthTo = now()->toDateString();
        $monthlySummary = $balances->period($report, $currentMonthFrom, $currentMonthTo);
        $currentMonthLabel = now()->translatedFormat('F Y');
        $categories = $report->transactions()->where('transactions.status', 'ACTIVE')->where('transactions.type', 'OUT')->whereBetween('transaction_date', [$from, $to])->join('expense_categories', 'expense_categories.id', '=', 'transactions.expense_category_id')->selectRaw('expense_categories.name, SUM(transactions.amount) total')->groupBy('expense_categories.id', 'expense_categories.name')->orderByDesc('total')->limit(6)->get();
        $latest = $config->show_latest_transactions ? $report->transactions()->where('status', 'ACTIVE')->latest('transaction_date')->latest('id')->limit(5)->get() : collect();
        $monthExpression = DB::getDriverName() === 'sqlite' ? "strftime('%Y-%m', transaction_date)" : "to_char(transaction_date, 'YYYY-MM')";
        $chartStartsOn = now()->startOfYear();
        $chartEndsOn = now();
        $monthlyTotals = $report->transactions()->where('status', 'ACTIVE')->whereBetween('transaction_date', [$chartStartsOn->toDateString(), $chartEndsOn->toDateString()])->selectRaw("{$monthExpression} AS period_month, SUM(CASE WHEN type='IN' THEN amount ELSE 0 END) AS incoming, SUM(CASE WHEN type='OUT' THEN amount ELSE 0 END) AS outgoing")->groupByRaw($monthExpression)->get()->keyBy('period_month');
        $months = collect(range(0, $chartEndsOn->month - 1))->map(function (int $monthOffset) use ($chartStartsOn, $monthlyTotals): object {
            $periodMonth = $chartStartsOn->copy()->addMonths($monthOffset)->format('Y-m');
            $totals = $monthlyTotals->get($periodMonth);

            return (object) [
                'period_month' => $periodMonth,
                'incoming' => (int) ($totals?->incoming ?? 0),
                'outgoing' => (int) ($totals?->outgoing ?? 0),
            ];
        });
        $qrSlide = $latest->isEmpty() ? 5 : 6;
        $slideCount = $qrSlide + 1;
        $publicPdfUrl = null;
        $qrCodeDataUri = null;

        if ($config->enabled && filled($token)) {
            $publicPdfUrl = rtrim((string) config('app.public_url'), '/').route('slides.pdf', ['token' => $token], false);
            $qrCodeDataUri = $qrCodes->dataUri($publicPdfUrl);
        }

        return view('slides.show', compact('report', 'config', 'from', 'to', 'summary', 'monthlySummary', 'currentMonthLabel', 'categories', 'latest', 'months', 'slideCount', 'qrSlide', 'qrCodeDataUri', 'publicPdfUrl', 'backgroundUrl'));
    }

    private function backgroundResponse(SlideConfig $config): StreamedResponse
    {
        $path = $config->settings['background_path'] ?? null;
        abort_unless(is_string($path) && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }
}
