<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Run;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Run>
 */
class RunFactory extends Factory
{
    public function definition(): array
    {
        $distanceM = fake()->numberBetween(2_000, 15_000);
        $paceSecPerKm = fake()->numberBetween(270, 420);

        return [
            'user_id' => User::factory(),
            'started_at' => fake()->dateTimeBetween('-60 days', 'now'),
            'distance_m' => $distanceM,
            'duration_sec' => (int) round($distanceM / 1000 * $paceSecPerKm),
            'calories' => fake()->optional()->numberBetween(150, 1_000),
        ];
    }
}
