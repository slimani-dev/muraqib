<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StackFactory extends Factory
{
    public function definition(): array
    {
        return [
            'external_id' => $this->faker->uuid(),
            'name' => $this->faker->domainWord(),
            'endpoint_id' => 1,
            'stack_status' => 1,
            'stack_type' => 2,
            'created_at_portainer' => now(),
        ];
    }
}
