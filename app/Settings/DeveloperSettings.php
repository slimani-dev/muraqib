<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class DeveloperSettings extends Settings
{
    public ?string $github_token;

    public ?string $posthog_project_key;

    public string $posthog_host;

    public static function group(): string
    {
        return 'developer';
    }

    public static function encrypted(): array
    {
        return [
            'github_token',
            'posthog_project_key',
        ];
    }
}
