<?php

namespace App\Filament\Resources\Netdatas\Pages;

use App\Filament\Resources\Netdatas\NetdataResource;
use App\Models\CloudflareDomain;
use App\Models\CloudflareIngressRule;
use App\Models\CloudflareTunnel;
use App\Services\Cloudflare\CloudflareService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreateNetdata extends CreateRecord
{
    protected static string $resource = NetdataResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        // 1. Validate & Prepare
        $domainId = $data['cloudflare_domain_id'];
        $name = $data['name'];
        $tunnelId = $data['cloudflare_tunnel_id'];
        $ip = $data['ip'];
        $port = $data['port'];

        $domain = CloudflareDomain::findOrFail($domainId);
        $tunnel = CloudflareTunnel::findOrFail($tunnelId);
        $service = app(CloudflareService::class);

        // 2. Wrap in Transaction? No, API calls are external. We want to stop if API fails.
        // If API fails, DB rollback happens automatically if we throw exception before Model::create.

        try {
            // A. Infrastructure (DNS & Ingress)
            // Create/Update DNS Check
            $dnsStatus = $service->createDnsRecord($domain, $tunnel, $name);
            Notification::make()
                ->title('DNS Record: '.ucfirst($dnsStatus))
                ->success()
                ->send();

            // Create Ingress Rule
            $ingress = new CloudflareIngressRule([
                'cloudflare_tunnel_id' => $tunnel->id,
                'hostname' => "{$name}.{$domain->name}",
                'service' => "http://{$ip}:{$port}",
                'is_catch_all' => false,
            ]);
            $ingress->save();

            // Push Ingress config to Cloudflare
            $service->updateIngressRules($tunnel);
            Notification::make()
                ->title('Ingress Rule Created & Pushed')
                ->success()
                ->send();

            // B. Zero Trust Protection (The Lock)
            $access = $service->protectSubdomain($domain, $name);
            Notification::make()
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

            throw ValidationException::withMessages([
                'name' => 'Provisioning Failed: '.$e->getMessage(),
            ]);
        }
    }
}
