<?php

namespace Database\Factories;

use App\Models\Cloudflare;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cloudflare>
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
