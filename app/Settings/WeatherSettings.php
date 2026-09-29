<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * The weather widget's location, and an optional Unsplash key for its background photo.
 * Edited in the admin panel under Settings → Weather.
 */
class WeatherSettings extends Settings
{
    public ?float $latitude;

    public ?float $longitude;

    public ?string $location_name;

    public ?string $unsplash_key;

    public static function group(): string
    {
        return 'weather';
    }

    /**
     * @return list<string>
     */
    public static function encrypted(): array
    {
        return ['unsplash_key'];
    }

    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
