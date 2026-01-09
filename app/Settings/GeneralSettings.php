<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{

    public static function group(): string
    {
        return 'general';
    }

    public array $data = [];
}