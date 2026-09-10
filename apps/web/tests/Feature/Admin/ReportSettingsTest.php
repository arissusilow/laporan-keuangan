<?php

namespace Tests\Feature\Admin;

use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportSettingsTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_general_settings_form_submits_to_the_report_update_route(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();

        $this->actingAs($admin)->get(route('reports.settings', ['report' => $report, 'tab' => 'general']))
            ->assertOk()
            ->assertSee('action="'.route('reports.update', $report).'"', false);

        $this->actingAs($admin)->put(route('reports.update', $report), [
            'name' => 'Laporan Uji Diperbarui',
            'description' => null,
            'starts_on' => null,
            'ends_on' => null,
            'opening_balance' => $report->opening_balance,
            'opening_balance_reason' => null,
            'status' => 'ARCHIVED',
            'color' => '#12372A',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_sessions', [
            'id' => $report->id,
            'name' => 'Laporan Uji Diperbarui',
            'status' => 'ARCHIVED',
        ]);
    }

    public function test_settings_shows_registered_users_as_email_autocomplete_options(): void
    {
        $admin = User::factory()->create();
        $candidate = User::factory()->create(['name' => 'Operator Baru', 'email' => 'operator@example.test']);
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();

        $this->actingAs($admin)->get(route('reports.settings', ['report' => $report, 'tab' => 'members']))
            ->assertOk()
            ->assertSee('list="registered-users"', false)
            ->assertSee($candidate->email)
            ->assertSee($candidate->name);
    }

    public function test_report_admin_can_update_and_remove_a_member(): void
    {
        $admin = User::factory()->create();
        $operator = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $member = ReportSessionMember::factory()->for($report, 'report')->for($operator)->create();

        $this->actingAs($admin)->put(route('members.update', [$report, $member]), [
            'email' => $operator->email,
            'role' => 'OFFICER',
            'can_add_in' => 1,
            'can_add_out' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('report_session_members', [
            'id' => $member->id,
            'role' => 'OFFICER',
            'can_add_in' => true,
            'can_add_out' => true,
        ]);

        $this->actingAs($admin)->delete(route('members.destroy', [$report, $member]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('report_session_members', ['id' => $member->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'MEMBER_REMOVED', 'report_session_id' => $report->id]);
    }

    public function test_last_report_admin_cannot_be_removed(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create();
        $membership = ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();

        $this->actingAs($admin)->delete(route('members.destroy', [$report, $membership]))
            ->assertSessionHasErrors('member');

        $this->assertDatabaseHas('report_session_members', ['id' => $membership->id]);
    }

    public function test_unused_category_can_be_deleted_but_used_category_is_preserved(): void
    {
        $admin = User::factory()->create();
        $report = ReportSession::factory()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        $unused = ExpenseCategory::factory()->for($report, 'report')->create(['name' => 'Belum Dipakai']);
        $used = ExpenseCategory::factory()->for($report, 'report')->create(['name' => 'Sudah Dipakai']);
        FinancialTransaction::factory()->outgoing($used)->for($admin, 'creator')->create();

        $this->actingAs($admin)->delete(route('categories.destroy', [$report, $unused]))
            ->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('expense_categories', ['id' => $unused->id]);

        $this->actingAs($admin)->delete(route('categories.destroy', [$report, $used]))
            ->assertSessionHasErrors('category');
        $this->assertDatabaseHas('expense_categories', ['id' => $used->id]);
    }
}
