<?php

namespace Database\Factories;

use App\Models\GitAccount;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'git_account_id' => GitAccount::factory(),
            'owner' => fake()->userName(),
            'name' => fake()->slug(2),
            'is_managed' => false,
        ];
    }

    public function managed(): static
    {
        return $this->state(['is_managed' => true]);
    }

    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => fake()->sentence(),
            'html_url' => "https://github.com/{$attributes['owner']}/{$attributes['name']}",
            'stars' => fake()->numberBetween(0, 5000),
            'forks' => fake()->numberBetween(0, 500),
            'open_issues' => fake()->numberBetween(0, 50),
            'open_pull_requests' => fake()->numberBetween(0, 10),
            'synced_at' => now(),
        ]);
    }
}
