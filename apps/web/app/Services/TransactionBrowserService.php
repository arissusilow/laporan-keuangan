<?php

namespace App\Services;

use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransactionBrowserService
{
    /** @return array{breakdown: Collection|LengthAwarePaginator, totals: array{incoming: int, outgoing: int, net: int}, transaction_count: int} */
    public function build(ReportSession $report, array $filters, string $mode, CarbonImmutable $anchor, int $page, string $path, array $query): array
    {
        [$from, $to] = match ($mode) {
            'day', 'week' => [$anchor->startOfMonth(), $anchor->endOfMonth()],
            'month' => [$anchor->startOfYear(), $anchor->endOfYear()],
            default => [null, null],
        };
        $base = fn (): Builder => $this->filteredQuery($report, $filters, $from, $to);
        $totalRow = $this->aggregate($base())->first();
        $totals = $this->moneyTotals($totalRow);

        $breakdown = match ($mode) {
            'day' => $this->days($base, $filters['sort'] ?? 'desc', $page, $path, $query),
            'week' => $this->weeks($base, $from, $to),
            'month' => $this->months($base, $anchor),
            default => $this->years($base),
        };

        return [
            'breakdown' => $breakdown,
            'totals' => $totals,
            'transaction_count' => (int) ($totalRow?->active_count ?? 0),
        ];
    }

    private function filteredQuery(ReportSession $report, array $filters, ?CarbonImmutable $from, ?CarbonImmutable $to): Builder
    {
        return $report->transactions()->getQuery()
            ->when($from, fn (Builder $query) => $query->whereDate('transaction_date', '>=', $from->toDateString()))
            ->when($to, fn (Builder $query) => $query->whereDate('transaction_date', '<=', $to->toDateString()))
            ->when($filters['type'] ?? null, fn (Builder $query, string $value) => $query->where('type', $value))
            ->when($filters['category'] ?? null, fn (Builder $query, int|string $value) => $query->where('expense_category_id', $value))
            ->when($filters['q'] ?? null, fn (Builder $query, string $value) => $query->whereRaw("LOWER(COALESCE(description, '')) LIKE ?", ['%'.mb_strtolower($value).'%']));
    }

    private function aggregate(Builder $query): Builder
    {
        return $query->selectRaw("COALESCE(SUM(CASE WHEN status = 'ACTIVE' AND type = 'IN' THEN amount ELSE 0 END), 0) AS incoming")
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'ACTIVE' AND type = 'OUT' THEN amount ELSE 0 END), 0) AS outgoing")
            ->selectRaw("SUM(CASE WHEN status = 'ACTIVE' THEN 1 ELSE 0 END) AS active_count");
    }

    /** @return array{incoming: int, outgoing: int, net: int} */
    private function moneyTotals(?object $row): array
    {
        $incoming = (int) ($row?->incoming ?? 0);
        $outgoing = (int) ($row?->outgoing ?? 0);

        return ['incoming' => $incoming, 'outgoing' => $outgoing, 'net' => $incoming - $outgoing];
    }

    private function row(string $label, string $secondary, ?object $aggregate, Collection $transactions = new Collection): array
    {
        return $this->moneyTotals($aggregate) + [
            'label' => $label,
            'secondary' => $secondary,
            'count' => (int) ($aggregate?->active_count ?? 0),
            'transactions' => $transactions,
        ];
    }

    private function days(callable $base, string $direction, int $page, string $path, array $query): LengthAwarePaginator
    {
        $groups = $this->aggregate($base())
            ->addSelect('transaction_date')
            ->groupBy('transaction_date')
            ->orderBy('transaction_date', $direction)
            ->paginate(10, ['*'], 'page', $page);
        $dates = $groups->getCollection()->pluck('transaction_date')->map(fn ($date) => CarbonImmutable::parse($date)->toDateString());
        $transactions = $dates->isEmpty()
            ? collect()
            : $base()->with('category')->where(function (Builder $query) use ($dates): void {
                foreach ($dates as $date) {
                    $query->orWhereDate('transaction_date', $date);
                }
            })->orderBy('transaction_date', $direction)->orderBy('id', $direction)->get()
                ->groupBy(fn (FinancialTransaction $transaction) => $transaction->transaction_date->toDateString());
        $groups->setCollection($groups->getCollection()->map(function (object $aggregate) use ($transactions): array {
            $date = CarbonImmutable::parse($aggregate->transaction_date);

            return $this->row(
                $date->format('d'),
                $date->locale('id')->translatedFormat('l, d M Y'),
                $aggregate,
                $transactions->get($date->toDateString(), collect()),
            );
        }));
        $groups->withPath($path)->appends($query);

        return $groups;
    }

    private function weeks(callable $base, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        return collect()->range(0, 5)->map(function (int $offset) use ($base, $from, $to): ?array {
            $weekStart = $from->startOfWeek(CarbonImmutable::SUNDAY)->addWeeks($offset);
            if ($weekStart->greaterThan($to)) {
                return null;
            }
            $weekEnd = $weekStart->addDays(6);
            $aggregate = $this->aggregate($base()->whereBetween('transaction_date', [
                max($weekStart, $from)->toDateString(),
                min($weekEnd, $to)->toDateString(),
            ]))->first();

            return $this->row('Minggu '.($offset + 1), $weekStart->format('d.m').' – '.$weekEnd->format('d.m'), $aggregate);
        })->filter()->reverse()->values();
    }

    private function months(callable $base, CarbonImmutable $anchor): Collection
    {
        $expression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%m', transaction_date) AS INTEGER)" : 'EXTRACT(MONTH FROM transaction_date)';
        $rows = $this->aggregate($base())->selectRaw("{$expression} AS period_number")->groupByRaw($expression)->get()->keyBy(fn ($row) => (int) $row->period_number);

        return collect()->range(1, 12)->map(function (int $month) use ($anchor, $rows): array {
            $date = $anchor->setMonth($month)->startOfMonth();

            return $this->row($date->locale('id')->translatedFormat('M'), $date->locale('id')->translatedFormat('F'), $rows->get($month));
        })->reverse()->values();
    }

    private function years(callable $base): Collection
    {
        $expression = DB::getDriverName() === 'sqlite' ? "CAST(strftime('%Y', transaction_date) AS INTEGER)" : 'EXTRACT(YEAR FROM transaction_date)';

        return $this->aggregate($base())
            ->selectRaw("{$expression} AS period_number")
            ->groupByRaw($expression)
            ->orderByDesc('period_number')
            ->get()
            ->map(fn (object $aggregate): array => $this->row((string) $aggregate->period_number, 'Tahun '.$aggregate->period_number, $aggregate));
    }
}
