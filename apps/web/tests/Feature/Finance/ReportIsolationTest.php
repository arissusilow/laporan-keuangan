<?php

namespace Tests\Feature\Finance;

use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportIsolationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_only_sees_reports_granted_to_their_account(): void
    {
        $user = User::factory()->create();
        $allowed = ReportSession::factory()->create(['name' => 'Laporan Milik Pengguna']);
        $other = ReportSession::factory()->create(['name' => 'Laporan Rahasia Lain']);
        ReportSessionMember::factory()->for($allowed, 'report')->for($user)->create();

        $response = $this->actingAs($user)->get('/laporan');

        $response->assertSee('Laporan Milik Pengguna');
        $response->assertDontSee('Laporan Rahasia Lain');
        $this->actingAs($user)->get(route('reports.dashboard', $other))->assertNotFound();
    }

    public function test_empty_database_shows_an_empty_state_without_example_data(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get('/laporan')
            ->assertSee('Belum ada Laporan')
            ->assertDontSee('Masjid AlHajj')
            ->assertDontSee('Dana Sosial 2026');
    }

    public function test_super_admin_can_render_and_create_a_report(): void
    {
        $user = User::factory()->superAdmin()->create();

        $this->actingAs($user)->get(route('reports.create'))
            ->assertOk()
            ->assertSee('Nama Laporan')
            ->assertDontSee("@include('reports.partials.fields", false);

        $this->actingAs($user)->post(route('reports.store'), [
            'name' => 'Dana Operasional',
            'description' => 'Pencatatan operasional utama',
            'starts_on' => '2026-09-01',
            'ends_on' => null,
            'opening_balance' => 250000,
            'color' => '#12372A',
        ])->assertRedirect();

        $report = ReportSession::query()->where('name', 'Dana Operasional')->firstOrFail();
        $this->assertDatabaseHas('report_session_members', [
            'report_session_id' => $report->id,
            'user_id' => $user->id,
            'role' => 'ADMIN',
        ]);
        $this->assertDatabaseHas('slide_configs', ['report_session_id' => $report->id]);
        $this->assertDatabaseHas('audit_logs', ['report_session_id' => $report->id, 'action' => 'REPORT_CREATED']);
    }
}
