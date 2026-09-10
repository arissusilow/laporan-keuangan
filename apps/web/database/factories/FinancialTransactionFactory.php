<?php

namespace Database\Factories;

use App\Models\ExpenseCategory;
use App\Models\FinancialTransaction;
use App\Models\ReportSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FinancialTransaction> */
class FinancialTransactionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'report_session_id' => ReportSession::factory(),
            'expense_category_id' => null,
            'created_by' => User::factory(),
            'number' => fake()->unique()->numerify('TRX-######'),
            'transaction_date' => fake()->date(),
            'type' => 'IN',
            'amount' => fake()->numberBetween(1, 10_000_000),
            'description' => fake()->sentence(),
            'status' => 'ACTIVE',
        ];
    }

    public function outgoing(ExpenseCategory $category): static
    {
        return $this->state(fn (array $attributes): array => [
            'report_session_id' => $category->report_session_id,
            'expense_category_id' => $category->id,
            'type' => 'OUT',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'CANCELLED',
            'cancellation_reason' => 'Koreksi pencatatan',
            'cancelled_at' => now(),
        ]);
    }
}
