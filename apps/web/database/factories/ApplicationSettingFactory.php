<?php

namespace Database\Factories;

use App\Models\ApplicationSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ApplicationSetting> */
class ApplicationSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'group' => 'identity',
            'key' => fake()->unique()->slug(2),
            'value' => fake()->words(2, true),
        ];
    }
}
