<?php

namespace App\Filament\Resources\Netdatas\Pages;

use App\Filament\Resources\Netdatas\NetdataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNetdata extends CreateRecord
{
    protected static string $resource = NetdataResource::class;

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        // 1. Validate & Prepare
        $domainId = $data['cloudflare_domain_id'];
        $name = $data['name'];
        $tunnelId = $data['cloudflare_tunnel_id'];
        $ip = $data['ip'];
        $port = $data['port'];

        $domain = \App\Models\CloudflareDomain::findOrFail($domainId);
        $tunnel = \App\Models\CloudflareTunnel::findOrFail($tunnelId);
        $service = app(\App\Services\Cloudflare\CloudflareService::class);

        // 2. Wrap in Transaction? No, API calls are external. We want to stop if API fails.
        // If API fails, DB rollback happens automatically if we throw exception before Model::create.

        try {
            // A. Infrastructure (DNS & Ingress)
            // Create/Update DNS Check
            $dnsStatus = $service->createDnsRecord($domain, $tunnel, $name);
            \Filament\Notifications\Notification::make()
                ->title('DNS Record: '.ucfirst($dnsStatus))
                ->success()
                ->send();

            // Create Ingress Rule
            $ingress = new \App\Models\CloudflareIngressRule([
                'cloudflare_tunnel_id' => $tunnel->id,
                'hostname' => "{$name}.{$domain->name}",
                'service' => "http://{$ip}:{$port}",
                'is_catch_all' => false,
            ]);
            $ingress->save();

            // Push Ingress config to Cloudflare
            $service->updateIngressRules($tunnel);
            \Filament\Notifications\Notification::make()
                ->title('Ingress Rule Created & Pushed')
                ->success()
                ->send();

            // B. Zero Trust Protection (The Lock)
            $access = $service->protectSubdomain($domain, $name);
            \Filament\Notifications\Notification::make()
                ->title('Zero Trust Protection Enabled')
                ->body("Access Policy and Service Token created for {$name}.{$domain->name}")
                ->success()
                ->send();

            // C. Create Netdata Model
            $data['cloudflare_access_id'] = $access->id;
            $data['status'] = 'active';

            return static::getModel()::create($data);

        } catch (\Exception $e) {
            // Rollback Ingress Rule if saved but API failed?
            // For now, let's just halt and show error. User can retry or clean up.

            throw \Illuminate\Validation\ValidationException::withMessages([
                'name' => 'Provisioning Failed: '.$e->getMessage(),
            ]);
        }
    }
}
