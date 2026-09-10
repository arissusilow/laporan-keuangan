<?php

namespace Database\Factories;

use App\Models\ReportSession;
use App\Models\ReportSessionMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportSessionMember> */
class ReportSessionMemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_session_id' => ReportSession::factory(),
            'user_id' => User::factory(),
            'role' => 'VIEWER',
            'can_add_in' => false,
            'can_add_out' => false,
            'can_edit_own' => false,
            'can_edit_all' => false,
            'can_cancel' => false,
            'can_export_pdf' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes): array => ['role' => 'ADMIN']);
    }

    public function officer(array $permissions = []): static
    {
        return $this->state(fn (array $attributes): array => ['role' => 'OFFICER'] + $permissions);
    }
}
