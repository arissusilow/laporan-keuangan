<?php

namespace Tests\Feature\Services;

use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class BalanceServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_balance_and_period_ignore_cancelled_transactions(): void
    {
        $user = User::factory()->create();
        $report = ReportSession::factory()->create(['opening_balance' => 1000]);
        $category = ExpenseCategory::factory()->for($report, 'report')->create();
        FinancialTransaction::factory()->for($report, 'report')->for($user, 'creator')->create(['transaction_date' => '2026-01-01', 'amount' => 500]);
        FinancialTransaction::factory()->outgoing($category)->for($user, 'creator')->create(['transaction_date' => '2026-01-05', 'amount' => 200]);
        FinancialTransaction::factory()->outgoing($category)->cancelled()->for($user, 'creator')->create(['transaction_date' => '2026-01-06', 'amount' => 900]);

        $service = app(BalanceService::class);

        $this->assertSame(1300, $service->balance($report));
        $this->assertSame(['incoming' => 500, 'outgoing' => 200, 'net' => 300, 'before' => 1000, 'ending' => 1300], $service->period($report, '2026-01-01', '2026-01-31'));
    }
}
