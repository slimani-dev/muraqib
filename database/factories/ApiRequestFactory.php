<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ApiRequest>
 */
class ApiRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service' => $this->faker->randomElement(['Cloudflare', 'Portainer', 'Netdata']),
            'name' => $this->faker->word,
            'method' => $this->faker->randomElement(['GET', 'POST', 'PUT', 'DELETE']),
            'url' => $this->faker->url,
            'request_headers' => ['Accept' => ['application/json']],
            'request_body' => null,
            'status_code' => $this->faker->randomElement([200, 201, 400, 404, 500]),
            'response_headers' => ['Content-Type' => ['application/json']],
            'response_body' => json_encode(['data' => $this->faker->words(3)]),
            'duration_ms' => $this->faker->numberBetween(50, 2000),
            'user_id' => \App\Models\User::factory(),
            'ip_address' => $this->faker->ipv4,
            'created_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ];
    }
}
