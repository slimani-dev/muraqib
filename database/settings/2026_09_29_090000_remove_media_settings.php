<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

/**
 * Media services are managed in the admin panel (media_services table) now; the old settings are unused.
 */
return new class extends SettingsMigration
{
    public function up(): void
    {
        foreach (['jellyfin_url', 'jellyfin_api_key', 'seerr_url', 'seerr_api_key', 'transmission_url', 'transmission_username', 'transmission_password'] as $name) {
            $this->migrator->deleteIfExists("media.{$name}");
        }
    }
};
