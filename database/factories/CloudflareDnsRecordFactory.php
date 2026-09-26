<?php

namespace Database\Factories;

use App\Models\CloudflareDnsRecord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CloudflareDnsRecord>
 */
class CloudflareDnsRecordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'record_id' => $this->faker->uuid(),
            'type' => 'CNAME',
            'name' => $this->faker->domainName(),
            'content' => $this->faker->domainName(),
            'proxied' => true,
            'ttl' => 1,
        ];
    }
}
