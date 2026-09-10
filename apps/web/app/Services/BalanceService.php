<?php

namespace App\Services;

use App\Models\ReportSession;

class BalanceService
{
    public function totals(ReportSession $report, ?string $from = null, ?string $to = null, array $filters = []): array
    {
        $query = $report->transactions()->where('status', 'ACTIVE');
        if ($from) {
            $query->whereDate('transaction_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('transaction_date', '<=', $to);
        }
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (! empty($filters['category'])) {
            $query->where('expense_category_id', $filters['category']);
        }
        $incoming = (clone $query)->where('type', 'IN')->sum('amount');
        $outgoing = (clone $query)->where('type', 'OUT')->sum('amount');

        return ['incoming' => (int) $incoming, 'outgoing' => (int) $outgoing, 'net' => (int) $incoming - (int) $outgoing];
    }

    public function balance(ReportSession $report, ?string $through = null): int
    {
        $totals = $this->totals($report, null, $through);

        return (int) $report->opening_balance + $totals['net'];
    }

    public function period(ReportSession $report, string $from, string $to, array $filters = []): array
    {
        $beforeQuery = $report->transactions()->where('status', 'ACTIVE')->whereDate('transaction_date', '<', $from);
        $before = (int) $report->opening_balance + (int) (clone $beforeQuery)->where('type', 'IN')->sum('amount') - (int) (clone $beforeQuery)->where('type', 'OUT')->sum('amount');
        $totals = $this->totals($report, $from, $to, $filters);

        return $totals + ['before' => $before, 'ending' => $before + $totals['net']];
    }
}
