<?php

namespace Database\Factories;

use App\Models\CloudflareAccess;
use App\Models\CloudflareDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloudflareAccess>
 */
class CloudflareAccessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'cloudflare_domain_id' => CloudflareDomain::factory(),
            'app_id' => $this->faker->uuid(),
            'name' => $this->faker->domainName(),
            'client_id' => $this->faker->md5().'.access',
            'service_token_id' => $this->faker->uuid(),
            'client_secret' => $this->faker->sha256(),
            'policy_id' => $this->faker->uuid(),
        ];
    }
}
