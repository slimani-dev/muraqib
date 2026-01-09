<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class MediaSettings extends Settings
{
    public string $jellyfin_url;

    public ?string $jellyfin_api_key;

    public string $jellyseerr_url;

    public ?string $jellyseerr_api_key;

    public string $transmission_url;

    public string $transmission_username;

    public ?string $transmission_password;

    public static function group(): string
    {
        return 'media';
    }

    public static function encrypted(): array
    {
        return [
            'jellyfin_api_key',
            'jellyseerr_api_key',
            'transmission_password',
        ];
    }
}
