<?php

namespace Tests\Feature\Finance;

use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TransactionRulesTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_outgoing_transaction_is_stored_in_the_active_report(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $category = ExpenseCategory::factory()->for($report, 'report')->create();

        $this->actingAs($user)->get(route('reports.dashboard', $report))
            ->assertOk()
            ->assertSee('Buat Transaksi')
            ->assertDontSee('＋ Uang Masuk')
            ->assertDontSee('− Uang Keluar');

        $this->actingAs($user)->get(route('transactions.create', $report))
            ->assertOk()
            ->assertSee('Buat Transaksi')
            ->assertSee('data-transaction-type="OUT"', false)
            ->assertSee('data-transaction-type="IN"', false)
            ->assertSee('data-transaction-form', false);

        $this->actingAs($user)->get(route('transactions.create', [$report, 'out']))
            ->assertOk()
            ->assertSee('data-rupiah-input', false)
            ->assertSee('Pemisah ribuan ditambahkan otomatis')
            ->assertSee('data-rupiah-preset="50000"', false)
            ->assertSee('Rp200.000')
            ->assertSee('data-rupiah-preset="500000"', false)
            ->assertSee('Rp1.000.000')
            ->assertSee('Ketik atau pilih kategori')
            ->assertSee('data-category-autocomplete', false)
            ->assertSee('transaction-form-card', false)
            ->assertSee('data-category-suggestion-list', false)
            ->assertSee('data-category-suggestion', false)
            ->assertSee('category-suggestions-in', false)
            ->assertSee('category-suggestions-out', false)
            ->assertSeeInOrder(['Kategori pengeluaran', 'Keterangan']);

        $this->actingAs($user)->post(route('transactions.store', [$report, 'out']), [
            'transaction_date' => '2026-09-08',
            'amount' => '125.000',
            'description' => 'Biaya operasional',
            'expense_category_id' => $category->id,
        ])->assertRedirectToRoute('reports.dashboard', $report);

        $this->assertDatabaseHas('transactions', ['report_session_id' => $report->id, 'type' => 'OUT', 'amount' => 125000, 'expense_category_id' => $category->id]);
        $this->assertDatabaseHas('audit_logs', ['report_session_id' => $report->id, 'action' => 'TRANSACTION_CREATED']);
        $this->actingAs($user)->get(route('reports.dashboard', $report))->assertOk();
    }

    public function test_outgoing_transaction_rejects_category_from_another_report(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $otherCategory = ExpenseCategory::factory()->create();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'out']), [
            'transaction_date' => '2026-09-08',
            'amount' => 125000,
            'description' => 'Biaya operasional',
            'expense_category_id' => $otherCategory->id,
        ])->assertSessionHasErrors(['expense_category_id' => 'Kategori harus aktif, sesuai jenis transaksi, dan berasal dari Laporan ini.']);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_incoming_transaction_can_store_a_matching_income_category(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $category = ExpenseCategory::factory()->incoming()->for($report, 'report')->create();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 500000,
            'description' => 'Penerimaan',
            'expense_category_id' => $category->id,
        ])->assertRedirectToRoute('reports.dashboard', $report);

        $this->assertDatabaseHas('transactions', [
            'report_session_id' => $report->id,
            'type' => 'IN',
            'amount' => 500000,
            'expense_category_id' => $category->id,
        ]);
    }

    public function test_incoming_transaction_requires_an_income_category(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 500000,
            'description' => 'Penerimaan tanpa kategori',
        ])->assertSessionHasErrors([
            'expense_category_id' => 'Pilih kategori atau tulis kategori baru.',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_transaction_can_be_stored_without_a_description(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $category = ExpenseCategory::factory()->incoming()->for($report, 'report')->create(['name' => 'Infak rutin']);

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 500000,
            'expense_category_id' => $category->id,
        ])->assertRedirectToRoute('reports.dashboard', $report);

        $this->assertDatabaseHas('transactions', [
            'report_session_id' => $report->id,
            'type' => 'IN',
            'description' => null,
        ]);
        $this->actingAs($user)->get(route('transactions.index', $report))
            ->assertOk()
            ->assertSee('Infak rutin')
            ->assertDontSee('Tanpa keterangan');
    }

    public function test_operator_can_create_and_use_a_new_category_while_storing_a_transaction(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'out']), [
            'transaction_date' => '2026-09-08',
            'amount' => 175000,
            'description' => 'Bantuan warga',
            'new_category_name' => 'Kegiatan Sosial',
        ])->assertRedirectToRoute('reports.dashboard', $report);

        $category = ExpenseCategory::query()->where([
            'report_session_id' => $report->id,
            'type' => 'OUT',
            'name' => 'Kegiatan Sosial',
            'active' => true,
        ])->firstOrFail();
        $this->assertDatabaseHas('transactions', [
            'report_session_id' => $report->id,
            'expense_category_id' => $category->id,
            'type' => 'OUT',
            'amount' => 175000,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'report_session_id' => $report->id,
            'action' => 'CATEGORY_CREATED_BY_OPERATOR',
        ]);
    }

    public function test_category_autocomplete_reuses_an_existing_category_case_insensitively(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $category = ExpenseCategory::factory()->incoming()->for($report, 'report')->create(['name' => 'Infak Rutin']);

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 300000,
            'new_category_name' => 'infak rutin',
        ])->assertRedirectToRoute('reports.dashboard', $report);

        $this->assertDatabaseCount('expense_categories', 1);
        $this->assertDatabaseHas('transactions', [
            'expense_category_id' => $category->id,
            'type' => 'IN',
            'amount' => 300000,
        ]);
    }

    public function test_incoming_transaction_rejects_an_outgoing_category_and_non_positive_amount(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $outgoingCategory = ExpenseCategory::factory()->for($report, 'report')->create();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 0,
            'description' => 'Penerimaan',
            'expense_category_id' => $outgoingCategory->id,
        ])->assertSessionHasErrors([
            'amount' => 'Nominal harus lebih dari nol.',
            'expense_category_id' => 'Kategori harus aktif, sesuai jenis transaksi, dan berasal dari Laporan ini.',
        ]);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_closed_report_rejects_new_transactions_with_conflict(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess(ReportSession::factory()->closed()->create());
        $category = ExpenseCategory::factory()->incoming()->for($report, 'report')->create();

        $this->actingAs($user)->post(route('transactions.store', [$report, 'in']), [
            'transaction_date' => '2026-09-08',
            'amount' => 1000,
            'description' => 'Tidak boleh disimpan',
            'expense_category_id' => $category->id,
        ])->assertStatus(409);

        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_unified_transaction_form_only_shows_types_allowed_for_the_operator(): void
    {
        $user = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->officer([
            'can_add_in' => true,
            'can_add_out' => false,
        ])->for($report, 'report')->for($user)->create();

        $this->actingAs($user)->get(route('transactions.create', $report))
            ->assertOk()
            ->assertSee('data-transaction-type="IN"', false)
            ->assertDontSee('data-transaction-type="OUT"', false);

        $this->actingAs($user)->get(route('transactions.create', [$report, 'out']))
            ->assertForbidden();
    }

    public function test_transaction_period_tabs_filter_data_and_provide_previous_and_next_navigation(): void
    {
        [$user, $report] = $this->officerWithTransactionAccess();
        $incomeCategory = ExpenseCategory::factory()->incoming()->for($report, 'report')->create();
        FinancialTransaction::factory()->for($report, 'report')->for($user, 'creator')->create([
            'expense_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-09-08',
            'amount' => 500000,
            'description' => 'Pemasukan September',
        ]);
        FinancialTransaction::factory()->for($report, 'report')->for($user, 'creator')->create([
            'expense_category_id' => $incomeCategory->id,
            'transaction_date' => '2026-08-31',
            'amount' => 250000,
            'description' => 'Pemasukan Agustus',
        ]);

        $this->actingAs($user)->get(route('transactions.index', [
            'report' => $report,
            'view' => 'month',
            'period' => '2026-09-09',
        ]))->assertOk()
            ->assertSee('Harian')
            ->assertSee('Mingguan')
            ->assertSee('Bulanan')
            ->assertSee('Tahunan')
            ->assertSee('Ringkasan bulanan')
            ->assertSee('September')
            ->assertSee('Agustus')
            ->assertSee('Rp 500.000')
            ->assertSee('Rp 250.000')
            ->assertSee('view=month&amp;period=2025-01-01', false)
            ->assertSee('view=month&amp;period=2027-01-01', false);

        $this->actingAs($user)->get(route('transactions.index', [
            'report' => $report,
            'view' => 'day',
            'period' => '2026-09-08',
        ]))->assertOk()
            ->assertSee('September 2026')
            ->assertSee('aria-label="Pilih bulan dan tahun"', false)
            ->assertSee('aria-label="Buka kalender bulan dan tahun"', false)
            ->assertSee('type="month"', false)
            ->assertSee('value="2026-09"', false)
            ->assertSee('Pemasukan September')
            ->assertDontSee('Pemasukan Agustus')
            ->assertSee('view=day&amp;period=2026-08-01', false)
            ->assertSee('view=day&amp;period=2026-10-01', false);

        $this->actingAs($user)->get(route('transactions.index', [
            'report' => $report,
            'view' => 'day',
            'period' => '2026-08',
        ]))->assertOk()
            ->assertSee('Agustus 2026')
            ->assertSee('Pemasukan Agustus')
            ->assertDontSee('Pemasukan September');

        $this->actingAs($user)->get(route('transactions.index', [
            'report' => $report,
            'view' => 'week',
            'period' => '2026-09-08',
        ]))->assertOk()
            ->assertSee('Ringkasan mingguan')
            ->assertSee('Minggu 2')
            ->assertSee('06.09 – 12.09');

        $this->actingAs($user)->get(route('transactions.index', [
            'report' => $report,
            'view' => 'year',
            'period' => '2026-01-01',
        ]))->assertOk()
            ->assertSee('Ringkasan tahunan')
            ->assertSee('Total')
            ->assertSee('2026')
            ->assertSee('Rp 750.000');
    }

    /** @return array{User, ReportSession} */
    private function officerWithTransactionAccess(?ReportSession $report = null): array
    {
        $user = User::factory()->create();
        $report ??= ReportSession::factory()->create();
        ReportSessionMember::factory()->officer(['can_add_in' => true, 'can_add_out' => true])->for($report, 'report')->for($user)->create();

        return [$user, $report];
    }
}
