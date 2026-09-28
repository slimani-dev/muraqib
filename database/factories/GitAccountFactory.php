<?php

namespace Database\Factories;

use App\Enums\GitProvider;
use App\Models\GitAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GitAccount>
 */
class GitAccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => GitProvider::GitHub,
            'name' => 'GitHub',
            'token' => fake()->sha1(),
        ];
    }

    public function provider(GitProvider $provider, ?string $baseUrl = null): static
    {
        return $this->state([
            'provider' => $provider,
            'name' => $provider->getLabel(),
            'base_url' => $baseUrl,
        ]);
    }
}
