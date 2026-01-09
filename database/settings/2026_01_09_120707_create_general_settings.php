<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.site_name', 'Muraqib');
        $this->migrator->add('general.root_domain', 'localhost');
        $this->migrator->add('general.timezone', 'UTC');
        $this->migrator->add('general.puid', 1000);
        $this->migrator->add('general.pgid', 1000);

        $this->migrator->add('infrastructure.portainer_url', 'http://localhost:9000');
        $this->migrator->add('infrastructure.portainer_api_key', null);
        $this->migrator->add('infrastructure.proxmox_url', 'https://localhost:8006');
        $this->migrator->add('infrastructure.proxmox_user', 'root@pam');
        $this->migrator->add('infrastructure.proxmox_token_id', 'muraqib');
        $this->migrator->add('infrastructure.proxmox_secret', null);
        $this->migrator->add('infrastructure.cloudflare_email', 'admin@example.com');
        $this->migrator->add('infrastructure.cloudflare_api_token', null);
        $this->migrator->add('infrastructure.cloudflare_account_id', 'id');

        $this->migrator->add('media.jellyfin_url', 'http://localhost:8096');
        $this->migrator->add('media.jellyfin_api_key', null);
        $this->migrator->add('media.jellyseerr_url', 'http://localhost:5055');
        $this->migrator->add('media.jellyseerr_api_key', null);
        $this->migrator->add('media.transmission_url', 'http://localhost:9091/transmission/rpc');
        $this->migrator->add('media.transmission_username', 'admin');
        $this->migrator->add('media.transmission_password', null);

        $this->migrator->add('developer.github_token', null);
        $this->migrator->add('developer.posthog_project_key', null);
        $this->migrator->add('developer.posthog_host', 'us');
    }
};
