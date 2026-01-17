<?php

namespace Database\Factories;

use App\Enums\PortainerStatus;
use App\Models\Portainer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Portainer>
 */
class PortainerFactory extends Factory
{
    protected $model = Portainer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(2, true).' Portainer',
            'url' => 'https://portainer.'.fake()->domainName(),
            'access_token' => 'ptr_'.fake()->sha256(),
            'status' => PortainerStatus::Active,
            'version' => fake()->randomElement(['2.19.4', '2.19.3', '2.18.4', '2.20.0']),
            'uptime' => null,
            'last_synced_at' => null,
        ];
    }

    /**
     * Indicate that the portainer is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => PortainerStatus::Inactive,
        ]);
    }

    /**
     * Indicate that the portainer has been synced recently.
     */
    public function synced(): static
    {
        return $this->state(fn (array $attributes) => [
            'last_synced_at' => now(),
            'version' => '2.19.4',
            'data' => [
                'Version' => '2.19.4',
                'Edition' => 'Community Edition',
            ],
        ]);
    }
}
