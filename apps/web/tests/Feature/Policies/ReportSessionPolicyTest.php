<?php

namespace Tests\Feature\Policies;

use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ReportSessionPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_roles_only_receive_their_allowed_report_abilities(): void
    {
        $report = ReportSession::factory()->create();
        $otherReport = ReportSession::factory()->create();
        $admin = User::factory()->create();
        $viewer = User::factory()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        ReportSessionMember::factory()->admin()->for($report, 'report')->for($admin)->create();
        ReportSessionMember::factory()->for($report, 'report')->for($viewer)->create();

        $this->assertTrue(Gate::forUser($admin)->allows('view', $report));
        $this->assertTrue(Gate::forUser($admin)->allows('update', $report));
        $this->assertFalse(Gate::forUser($admin)->allows('view', $otherReport));
        $this->assertTrue(Gate::forUser($viewer)->allows('view', $report));
        $this->assertFalse(Gate::forUser($viewer)->allows('update', $report));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('update', $otherReport));
    }
}
