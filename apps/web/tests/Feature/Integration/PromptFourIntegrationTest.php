<?php

namespace Tests\Feature\Integration;

use App\Jobs\GeneratePdfExport;
use App\Models\ApplicationSetting;
use App\Models\Attachment;
use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\PdfExport;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PromptFourIntegrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_with_exactly_one_active_report_opens_its_database_dashboard(): void
    {
        $user = User::factory()->create(['password' => Hash::make('Rahasia123')]);
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->for($report, 'report')->for($user)->create();

        $this->post(route('login.attempt'), ['email' => $user->email, 'password' => 'Rahasia123'])
            ->assertRedirectToRoute('reports.dashboard', $report);
    }

    public function test_viewer_can_open_transaction_detail_but_does_not_see_mutation_actions(): void
    {
        $viewer = User::factory()->create();
        $creator = User::factory()->create();
        $report = ReportSession::factory()->create();
        $category = ExpenseCategory::factory()->incoming()->for($report, 'report')->create(['name' => 'Infak']);
        ReportSessionMember::factory()->for($report, 'report')->for($viewer)->create();
        $transaction = FinancialTransaction::factory()->for($report, 'report')->for($creator, 'creator')->create([
            'expense_category_id' => $category->id,
            'description' => 'Infak Jumat',
        ]);

        $this->actingAs($viewer)->get(route('transactions.show', [$report, $transaction]))
            ->assertOk()
            ->assertSee($transaction->number)
            ->assertSee('Infak Jumat')
            ->assertDontSee('Ubah transaksi')
            ->assertDontSee('Ya, batalkan transaksi');
    }

    public function test_officer_with_cancel_permission_can_cancel_from_detail_without_edit_permission(): void
    {
        $officer = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->officer(['can_cancel' => true])->for($report, 'report')->for($officer)->create();
        $transaction = FinancialTransaction::factory()->for($report, 'report')->for($officer, 'creator')->create();

        $this->actingAs($officer)->get(route('transactions.show', [$report, $transaction]))
            ->assertOk()
            ->assertDontSee('Ubah transaksi')
            ->assertSee('Ya, batalkan transaksi');

        $this->actingAs($officer)->post(route('transactions.cancel', [$report, $transaction]), ['reason' => 'Salah tanggal'])
            ->assertSessionHas('success', 'Transaksi dibatalkan.');
        $this->assertDatabaseHas('transactions', ['id' => $transaction->id, 'status' => 'CANCELLED']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'TRANSACTION_CANCELLED', 'report_session_id' => $report->id]);
    }

    public function test_transaction_detail_and_private_attachment_are_isolated_between_reports(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $allowed = ReportSession::factory()->create();
        $other = ReportSession::factory()->create();
        ReportSessionMember::factory()->for($allowed, 'report')->for($user)->create();
        $transaction = FinancialTransaction::factory()->for($other, 'report')->for($owner, 'creator')->create();
        Storage::disk('local')->put('attachments/other/bukti.pdf', 'private');
        $attachment = Attachment::query()->create([
            'report_session_id' => $other->id,
            'transaction_id' => $transaction->id,
            'disk' => 'local',
            'path' => 'attachments/other/bukti.pdf',
            'original_name' => 'bukti.pdf',
            'mime_type' => 'application/pdf',
            'size' => 7,
        ]);

        $this->actingAs($user)->get(route('transactions.show', [$other, $transaction]))->assertForbidden();
        $this->actingAs($user)->get(route('attachments.download', $attachment))->assertForbidden();
    }

    public function test_runtime_uses_application_identity_stored_in_database(): void
    {
        ApplicationSetting::query()->create(['group' => 'identity', 'key' => 'app_name', 'value' => 'Kas Bersama']);
        ApplicationSetting::query()->create(['group' => 'identity', 'key' => 'short_name', 'value' => 'KB']);

        $this->get(route('login'))->assertOk()->assertSee('Kas Bersama')->assertSee('KB');
    }

    public function test_pdf_job_uses_database_data_stores_private_file_and_audits_completion(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $report = ReportSession::factory()->create(['opening_balance' => 100_000]);
        FinancialTransaction::factory()->for($report, 'report')->for($user, 'creator')->create([
            'transaction_date' => '2026-09-01',
            'amount' => 250_000,
        ]);
        $export = PdfExport::query()->create([
            'report_session_id' => $report->id,
            'requested_by' => $user->id,
            'starts_on' => '2026-09-01',
            'ends_on' => '2026-09-30',
            'status' => 'PENDING',
        ]);

        (new GeneratePdfExport($export->id))->handle(app(BalanceService::class));

        $export->refresh();
        $this->assertSame('READY', $export->status);
        Storage::disk('local')->assertExists($export->path);
        $this->assertDatabaseHas('audit_logs', [
            'report_session_id' => $report->id,
            'user_id' => $user->id,
            'action' => 'PDF_READY',
        ]);
    }

    public function test_report_admin_can_store_pdf_configuration_used_by_exports(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();

        $this->actingAs($admin)->put(route('reports.pdf-settings.update', $report), [
            'title' => 'Laporan Transparansi Masjid',
            'footer' => 'Bendahara Masjid',
            'margin_mm' => 14,
        ])->assertRedirect(route('reports.settings', $report).'?tab=pdf');

        $this->assertSame('Laporan Transparansi Masjid', $report->fresh()->pdf_settings['title']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PDF_SETTINGS_UPDATED', 'report_session_id' => $report->id]);
        $this->actingAs($admin)->get(route('reports.settings', ['report' => $report, 'tab' => 'pdf']))
            ->assertOk()
            ->assertSee('Laporan Transparansi Masjid')
            ->assertSee('Bendahara Masjid');
    }

    public function test_only_super_admin_can_store_database_backed_backup_schedule(): void
    {
        $user = User::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $payload = ['backup_frequency' => 'WEEKLY', 'backup_time' => '03:30', 'backup_retention_daily' => 21];

        $this->actingAs($user)->put(route('admin.backups.settings'), $payload)->assertForbidden();
        $this->actingAs($superAdmin)->put(route('admin.backups.settings'), $payload)
            ->assertSessionHas('success', 'Jadwal dan retensi backup disimpan.');

        $this->assertDatabaseHas('application_settings', ['key' => 'backup_frequency']);
        $this->assertDatabaseHas('audit_logs', ['user_id' => $superAdmin->id, 'action' => 'BACKUP_SETTINGS_UPDATED']);
        $this->actingAs($superAdmin)->get(route('admin.system'))
            ->assertOk()
            ->assertSee('Kesehatan layanan')
            ->assertSee('Mingguan, setiap Senin');
    }
}
