<?php

namespace Database\Factories;

use App\Models\ReportSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportSession> */
class ReportSessionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'starts_on' => null,
            'ends_on' => null,
            'opening_balance' => 0,
            'currency' => 'IDR',
            'color' => '#12372A',
            'status' => 'ACTIVE',
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'CLOSED']);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes): array => ['status' => 'ARCHIVED']);
    }
}
