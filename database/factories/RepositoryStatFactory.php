<?php

namespace Database\Factories;

use App\Models\Repository;
use App\Models\RepositoryStat;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepositoryStat>
 */
class RepositoryStatFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repository_id' => Repository::factory(),
            'date' => today(),
            'stars' => fake()->numberBetween(0, 5000),
            'forks' => fake()->numberBetween(0, 500),
            'downloads' => fake()->numberBetween(0, 100000),
        ];
    }
}
