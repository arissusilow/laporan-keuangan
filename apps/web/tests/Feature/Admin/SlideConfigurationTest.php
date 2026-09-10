<?php

namespace Tests\Feature\Admin;

use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\SlideConfig;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SlideConfigurationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_report_admin_can_open_slide_preview_without_a_public_token(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create(['name' => 'Laporan Masjid']);
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $report->slideConfig()->create(['enabled' => false]);

        $this->actingAs($admin)->get(route('reports.settings', ['report' => $report, 'tab' => 'slide']))
            ->assertOk()
            ->assertSee(route('slides.preview', $report), false)
            ->assertSee('Pratinjau Admin');

        $this->actingAs($admin)->get(route('slides.preview', $report))
            ->assertOk()
            ->assertSee('Laporan Masjid')
            ->assertSee('tv-stage', false)
            ->assertSee('slide-controls', false)
            ->assertSee('data-slide-dot', false);
    }

    public function test_non_admin_cannot_open_slide_preview(): void
    {
        $user = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->for($report, 'report')->for($user)->create();
        $report->slideConfig()->create();

        $this->actingAs($user)->get(route('slides.preview', $report))
            ->assertForbidden();
    }

    public function test_admin_stores_private_slide_background_with_fill_crop_settings(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($user)->create();

        $this->actingAs($user)->put(route('slides.update', $report), [
            'duration_seconds' => 12,
            'refresh_seconds' => 60,
            'background_opacity' => 20,
            'background_image' => UploadedFile::fake()->image('identitas.jpg', 400, 900),
        ])->assertSessionHas('success', 'Pengaturan slide disimpan.');

        $config = SlideConfig::query()->whereBelongsTo($report, 'report')->sole();
        $this->assertSame('cover', $config->settings['background_fit']);
        $this->assertSame('center', $config->settings['background_position']);
        $this->assertSame(20, $config->settings['background_opacity']);
        $this->assertNotNull($config->public_token);
        $this->assertSame(hash('sha256', $config->public_token), $config->token_hash);
        $this->assertNotSame($config->public_token, DB::table('slide_configs')->where('id', $config->id)->value('public_token'));
        Storage::disk('local')->assertExists($config->settings['background_path']);
    }

    public function test_enabled_public_slide_can_be_opened_without_login(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create(['name' => 'Laporan TV Publik']);
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $report->slideConfig()->create();

        $this->actingAs($admin)->put(route('slides.update', $report), [
            'enabled' => 1,
            'duration_seconds' => 12,
            'refresh_seconds' => 60,
            'background_opacity' => 14,
        ])->assertSessionHasNoErrors();

        $config = $report->slideConfig()->firstOrFail();
        auth()->logout();

        $this->get(route('slides.show', $config->public_token))
            ->assertOk()
            ->assertSee('Laporan TV Publik')
            ->assertSee('Pindai untuk membuka PDF')
            ->assertSee('data:image/svg+xml;base64,', false)
            ->assertSee('slide-controls', false);
    }

    public function test_public_full_report_pdf_requires_an_enabled_valid_slide_token(): void
    {
        $report = ReportSession::factory()->create([
            'name' => 'Kas Masjid',
            'starts_on' => '2026-01-01',
        ]);
        $enabledConfig = $report->slideConfig()->create([
            'enabled' => true,
            'token_hash' => hash('sha256', 'token-publik'),
            'public_token' => 'token-publik',
        ]);
        FinancialTransaction::factory()->for($report, 'report')->create([
            'status' => 'ACTIVE',
            'transaction_date' => '2026-01-10',
            'description' => 'Infak Jumat',
        ]);

        $this->travelTo(CarbonImmutable::parse('2026-03-15 10:00:00'));

        $this->get(route('slides.pdf', 'token-publik'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('slides.pdf', 'token-salah'))->assertNotFound();

        $enabledConfig->update(['enabled' => false]);
        $this->get(route('slides.show', 'token-publik'))->assertNotFound();
        $this->get(route('slides.pdf', 'token-publik'))->assertNotFound();
    }

    public function test_monthly_cash_flow_chart_uses_january_through_the_current_month(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-03-15 10:00:00'));
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $report->slideConfig()->create();

        foreach ([
            ['2025-12-20', 999_999],
            ['2026-01-10', 111_111],
            ['2026-03-10', 333_333],
        ] as [$transactionDate, $amount]) {
            FinancialTransaction::factory()->for($report, 'report')->for($admin, 'creator')->create([
                'transaction_date' => $transactionDate,
                'amount' => $amount,
            ]);
        }

        $this->actingAs($admin)->get(route('slides.preview', ['report' => $report, 'screen' => 4]))
            ->assertOk()
            ->assertSee('Jan 26')
            ->assertSee('Feb 26')
            ->assertSee('Mar 26')
            ->assertSee('Masuk Rp 111.111', false)
            ->assertSee('Masuk Rp 0', false)
            ->assertSee('Masuk Rp 333.333', false)
            ->assertDontSee('999.999');
    }

    public function test_summary_slide_shows_previous_month_balance_and_current_month_cash_flow(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-09-15 10:00:00'));
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create(['opening_balance' => 1_000_000]);
        $category = ExpenseCategory::factory()->for($report, 'report')->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $report->slideConfig()->create();

        FinancialTransaction::factory()->for($report, 'report')->for($admin, 'creator')->create([
            'transaction_date' => '2026-08-20',
            'amount' => 500_000,
        ]);
        FinancialTransaction::factory()->for($report, 'report')->for($admin, 'creator')->create([
            'transaction_date' => '2026-09-05',
            'amount' => 250_000,
        ]);
        FinancialTransaction::factory()->outgoing($category)->for($admin, 'creator')->create([
            'transaction_date' => '2026-09-10',
            'amount' => 75_000,
        ]);

        $this->actingAs($admin)->get(route('slides.preview', ['report' => $report, 'screen' => 2]))
            ->assertOk()
            ->assertSee('Ringkasan September 2026')
            ->assertSee('Saldo bulan lalu')
            ->assertSee('Rp 1.500.000')
            ->assertSee('Pemasukan bulan ini')
            ->assertSee('Rp 250.000')
            ->assertSee('Pengeluaran bulan ini')
            ->assertSee('Rp 75.000');
    }
}
