<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cloudflare>
 */
class CloudflareFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company(),
            'account_id' => $this->faker->uuid(),
            'api_token' => $this->faker->sha256(),
            'status' => 'active',
        ];
    }
}
