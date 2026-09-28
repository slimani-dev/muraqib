<?php

namespace Database\Factories;

use App\Enums\MediaServiceType;
use App\Models\MediaService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaService>
 */
class MediaServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return $this->attributesFor(MediaServiceType::Radarr);
    }

    public function jellyfin(): static
    {
        return $this->state($this->attributesFor(MediaServiceType::Jellyfin));
    }

    public function seerr(): static
    {
        return $this->state($this->attributesFor(MediaServiceType::Seerr));
    }

    public function radarr(): static
    {
        return $this->state($this->attributesFor(MediaServiceType::Radarr));
    }

    public function sonarr(): static
    {
        return $this->state($this->attributesFor(MediaServiceType::Sonarr));
    }

    public function bazarr(): static
    {
        return $this->state($this->attributesFor(MediaServiceType::Bazarr));
    }

    public function transmission(): static
    {
        return $this->state([
            ...$this->attributesFor(MediaServiceType::Transmission),
            'api_key' => null,
            'username' => fake()->userName(),
            'password' => fake()->password(),
            'settings' => ['rpc_path' => '/transmission/rpc'],
        ]);
    }

    public function disabled(): static
    {
        return $this->state(['is_enabled' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFor(MediaServiceType $type): array
    {
        return [
            'type' => $type,
            'name' => $type->getLabel(),
            'url' => "https://{$type->value}.example.com",
            'api_key' => fake()->sha1(),
            'is_enabled' => true,
        ];
    }
}
