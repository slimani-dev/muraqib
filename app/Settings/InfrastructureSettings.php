<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class InfrastructureSettings extends Settings
{
    public string $portainer_url;

    public ?string $portainer_api_key;

    public string $proxmox_url;

    public string $proxmox_user;

    public string $proxmox_token_id;

    public ?string $proxmox_secret;

    public string $cloudflare_email;

    public ?string $cloudflare_api_token;

    public string $cloudflare_account_id;

    public static function group(): string
    {
        return 'infrastructure';
    }

    public static function encrypted(): array
    {
        return [
            'portainer_api_key',
            'proxmox_secret',
            'cloudflare_api_token',
        ];
    }
}
