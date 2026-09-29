<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Weather settings moved from .env to the admin panel (Settings → Weather). Values already in
 * .env (WEATHER_* and UNSPLASH_API_KEY) are carried over once; after that the page is the source.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        $latitude = env('WEATHER_LATITUDE');
        $longitude = env('WEATHER_LONGITUDE');

        $this->migrator->add('weather.latitude', is_numeric($latitude) ? (float) $latitude : null);
        $this->migrator->add('weather.longitude', is_numeric($longitude) ? (float) $longitude : null);
        $this->migrator->add('weather.location_name', env('WEATHER_LOCATION_NAME') ?: null);
        $this->migrator->addEncrypted('weather.unsplash_key', env('UNSPLASH_API_KEY') ?: null);
    }
};
