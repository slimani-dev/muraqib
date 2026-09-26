<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public string $root_domain;

    public string $timezone;

    public int $puid;

    public int $pgid;

    public static function group(): string
    {
        return 'general';
    }
}
