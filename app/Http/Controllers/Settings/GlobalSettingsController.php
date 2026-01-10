<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\DeveloperSettings;
use App\Settings\GeneralSettings;
use App\Settings\InfrastructureSettings;
use App\Settings\MediaSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GlobalSettingsController extends Controller
{
    public function general(GeneralSettings $general): Response
    {
        $timezones = collect(timezone_identifiers_list())->map(function ($timezone) {
            $date = new \DateTime('now', new \DateTimeZone($timezone));
            $offset = $date->getOffset() / 3600;
            $formattedOffset = ($offset >= 0 ? '+' : '').$offset;
            $currentTime = $date->format('H:i');

            return [
                'value' => $timezone,
                'label' => "(UTC{$formattedOffset}) {$timezone}",
                'time' => $currentTime,
            ];
        })->values();

        // Defaults from .env
        $defaults = [
            'site_name' => config('app.name', 'Muraqib'),
            'root_domain' => parse_url(config('app.url'), PHP_URL_HOST) ?? 'localhost',
        ];

        return Inertia::render('settings/General', [
            'settings' => $general->toArray(),
            'timezones' => $timezones,
            'defaults' => $defaults,
        ]);
    }

    public function updateGeneral(Request $request, GeneralSettings $general): RedirectResponse
    {
        $data = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'root_domain' => ['required', 'string', 'max:255'],
            'timezone' => ['required', 'string', 'timezone'],
            'puid' => ['required', 'integer'],
            'pgid' => ['required', 'integer'],
        ]);

        $general->site_name = $data['site_name'];
        $general->root_domain = $data['root_domain'];
        $general->timezone = $data['timezone'];
        $general->puid = $data['puid'];
        $general->pgid = $data['pgid'];
        $general->save();

        // Sync to .env
        \SoulDoit\SetEnv\Facades\Env::set('APP_NAME', $general->site_name);
        \SoulDoit\SetEnv\Facades\Env::set('APP_URL', 'https://'.$general->root_domain);
        \SoulDoit\SetEnv\Facades\Env::set('APP_TIMEZONE', $general->timezone);

        \Illuminate\Support\Facades\Artisan::call('config:clear');

        return redirect()->back()->with('success', 'General settings updated successfully.');
    }

    public function portainer(InfrastructureSettings $settings): Response
    {
        return Inertia::render('settings/vault/Portainer', [
            'settings' => $settings->toArray(),
        ]);
    }

    public function updatePortainer(Request $request, InfrastructureSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'portainer_url' => ['required', 'url'],
            'portainer_api_key' => ['nullable', 'string'],
        ]);

        $settings->portainer_url = $data['portainer_url'];
        if (! empty($data['portainer_api_key'])) {
            $settings->portainer_api_key = $data['portainer_api_key'];
        }

        $settings->save();

        return redirect()->back()->with('success', 'Portainer settings updated.');
    }

    public function cloudflare(InfrastructureSettings $settings): Response
    {
        $config = \App\Models\Cloudflare::first();

        return Inertia::render('settings/vault/Cloudflare', [
            'settings' => $settings->toArray(),
            'cloudflare_config' => $config ? $config->toArray() : null,
        ]);
    }

    public function updateCloudflare(Request $request, InfrastructureSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'cloudflare_email' => ['required', 'email'],
            'cloudflare_api_token' => ['nullable', 'string'],
            'cloudflare_account_id' => ['required', 'string'],
        ]);

        $settings->cloudflare_email = $data['cloudflare_email'];
        $settings->cloudflare_account_id = $data['cloudflare_account_id'];

        if (! empty($data['cloudflare_api_token'])) {
            $settings->cloudflare_api_token = $data['cloudflare_api_token'];
        }

        $settings->save();

        return redirect()->back()->with('success', 'Cloudflare settings updated.');
    }

    public function proxmox(InfrastructureSettings $settings): Response
    {
        return Inertia::render('settings/vault/Proxmox', [
            'settings' => $settings->toArray(),
        ]);
    }

    public function updateProxmox(Request $request, InfrastructureSettings $settings): RedirectResponse
    {
        $data = $request->validate([
            'proxmox_url' => ['required', 'url'],
            'proxmox_user' => ['required', 'string'],
            'proxmox_token_id' => ['required', 'string'],
            'proxmox_secret' => ['nullable', 'string'],
        ]);

        $settings->proxmox_url = $data['proxmox_url'];
        $settings->proxmox_user = $data['proxmox_user'];
        $settings->proxmox_token_id = $data['proxmox_token_id'];

        if (! empty($data['proxmox_secret'])) {
            $settings->proxmox_secret = $data['proxmox_secret'];
        }

        $settings->save();

        return redirect()->back()->with('success', 'Proxmox settings updated.');
    }

    public function media(MediaSettings $media): Response
    {
        return Inertia::render('settings/vault/Media', [
            'settings' => $media->toArray(),
        ]);
    }

    public function updateMedia(Request $request, MediaSettings $media): RedirectResponse
    {
        $data = $request->validate([
            'jellyfin_url' => ['required', 'url'],
            'jellyfin_api_key' => ['nullable', 'string'],
            'jellyseerr_url' => ['required', 'url'],
            'jellyseerr_api_key' => ['nullable', 'string'],
            'transmission_url' => ['required', 'url'],
            'transmission_username' => ['required', 'string'],
            'transmission_password' => ['nullable', 'string'],
        ]);

        $media->jellyfin_url = $data['jellyfin_url'];
        if (! empty($data['jellyfin_api_key'])) {
            $media->jellyfin_api_key = $data['jellyfin_api_key'];
        }
        $media->jellyseerr_url = $data['jellyseerr_url'];
        if (! empty($data['jellyseerr_api_key'])) {
            $media->jellyseerr_api_key = $data['jellyseerr_api_key'];
        }
        $media->transmission_url = $data['transmission_url'];
        $media->transmission_username = $data['transmission_username'];
        if (! empty($data['transmission_password'])) {
            $media->transmission_password = $data['transmission_password'];
        }
        $media->save();

        return redirect()->back()->with('success', 'Media settings updated.');
    }

    public function developer(DeveloperSettings $developer): Response
    {
        return Inertia::render('settings/vault/Developer', [
            'settings' => $developer->toArray(),
        ]);
    }

    public function updateDeveloper(Request $request, DeveloperSettings $developer): RedirectResponse
    {
        $data = $request->validate([
            'github_token' => ['nullable', 'string'],
            'posthog_project_key' => ['nullable', 'string'],
            'posthog_host' => ['required', 'string'],
        ]);

        if (! empty($data['github_token'])) {
            $developer->github_token = $data['github_token'];
        }
        if (! empty($data['posthog_project_key'])) {
            $developer->posthog_project_key = $data['posthog_project_key'];
        }
        $developer->posthog_host = $data['posthog_host'];
        $developer->save();

        return redirect()->back()->with('success', 'Developer settings updated.');
    }
}
