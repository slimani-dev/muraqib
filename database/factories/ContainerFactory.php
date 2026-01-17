<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContainerFactory extends Factory
{
    public function definition(): array
    {
        return [
            'container_id' => $this->faker->uuid(),
            'name' => $this->faker->domainWord(),
            'image' => 'nginx:latest',
            'state' => 'running',
            'status' => 'Up 2 hours',
            'stack_name' => $this->faker->domainWord(),
            'endpoint_id' => 1,
            'endpoint_name' => 'primary',
            'created_at_portainer' => now(),
        ];
    }
}
