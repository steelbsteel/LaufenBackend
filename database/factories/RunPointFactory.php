<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Run;
use App\Models\RunPoint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RunPoint>
 */
class RunPointFactory extends Factory
{
    public function definition(): array
    {
        return [
            'run_id' => Run::factory(),
            'lat' => fake()->latitude(),
            'lng' => fake()->longitude(),
            'recorded_at' => now(),
        ];
    }
}
