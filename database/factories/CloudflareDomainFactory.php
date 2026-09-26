<?php

namespace Database\Factories;

use App\Models\CloudflareDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloudflareDomain>
 */
class CloudflareDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->domainName(),
            'status' => 'active',
        ];
    }
}
