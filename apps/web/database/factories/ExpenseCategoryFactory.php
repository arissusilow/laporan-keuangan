<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\ReportSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ExpenseCategory> */
class ExpenseCategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_session_id' => ReportSession::factory(),
            'type' => 'OUT',
            'name' => fake()->unique()->words(2, true),
            'color' => '#64748B',
            'sort_order' => 0,
            'active' => true,
            'is_default' => false,
        ];
    }

    public function incoming(): static
    {
        return $this->state(fn (): array => ['type' => 'IN']);
    }
}
