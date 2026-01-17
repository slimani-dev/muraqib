<?php

namespace App\Filament\Resources\Cloudflares\Pages;

use App\Filament\Resources\Cloudflares\CloudflareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCloudflares extends ListRecords
{
    protected static string $resource = CloudflareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('setup_wizard')
                ->label('Setup Wizard')
                ->icon('mdi-wizard-hat')
                ->slideOver(false)
                ->modalWidth('7xl')
                ->steps(\App\Filament\Resources\Cloudflares\Schemas\WizardCloudflareForm::getSteps())
                ->action(function (array $data) {
                    try {
                        // 1. Create Account
                        $account = \App\Models\Cloudflare::create([
                            'name' => $data['name'],
                            'account_id' => $data['account_id'],
                            'api_token' => $data['api_token'],
                        ]);

                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        // 2. Handle Tunnel
                        if ($data['tunnel_mode'] === 'create') {
                            $remoteTunnel = $service->findOrCreateTunnel($account, $data['new_tunnel_name']);
                            $tunnelData = $remoteTunnel;
                        } else {
                            // Fetch the actual tunnel name from cache
                            $cacheKey = 'wizard_tunnels_'.md5($account->api_token);
                            $tunnelName = 'Existing Tunnel'; // Fallback

                            if (\Illuminate\Support\Facades\Cache::has($cacheKey)) {
                                $tunnels = \Illuminate\Support\Facades\Cache::get($cacheKey);
                                $existingTunnel = $tunnels->firstWhere('id', $data['existing_tunnel_id']);
                                if ($existingTunnel) {
                                    $tunnelName = $existingTunnel['name'];
                                }
                            }

                            $tunnelData = ['id' => $data['existing_tunnel_id'], 'name' => $tunnelName];
                        }

                        if (! $tunnelData) {
                            throw new \Exception('Failed to resolve tunnel.');
                        }

                        $tunnel = $account->tunnels()->create([
                            'tunnel_id' => $tunnelData['id'],
                            'name' => $tunnelData['name'],
                            'is_active' => true,
                        ]);

                        // Sync tunnel status from Cloudflare
                        try {
                            $details = $service->getTunnelDetails($tunnel);
                            if ($details) {
                                $tunnel->update([
                                    'status' => $details['status'],
                                    'conns_active_at' => $details['conns_active_at'] ?? null,
                                    'client_version' => $details['connections'][0]['client_version'] ?? null,
                                    'is_active' => ($details['status'] === 'healthy'),
                                    'status_checked_at' => now(),
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Log but don't fail - tunnel is created, status sync can be done later
                        }

                        // 3. Sync Domains
                        $zones = $service->listZones($account->api_token);
                        foreach ($zones as $z) {
                            $account->domains()->firstOrCreate(
                                ['zone_id' => $z['id']],
                                ['name' => $z['name'], 'is_active' => ($z['status'] === 'active')]
                            );
                        }

                        // 4. Create Ingress Rules & DNS
                        $selectedZoneId = $data['zone_id'];
                        $domainModel = $account->domains()->where('zone_id', $selectedZoneId)->firstOrFail();

                        foreach ($data['ingress_rules'] as $rule) {
                            $tunnel->ingressRules()->create([
                                'hostname' => $rule['hostname'],
                                'service' => $rule['service'],
                                'path' => $rule['path'] ?? null,
                                'origin_request' => $rule['origin_request'] ?? null,
                                'is_catch_all' => false,
                            ]);

                            $service->ensureCnameRecord(
                                $domainModel,
                                $rule['hostname'],
                                "{$tunnel->tunnel_id}.cfargotunnel.com"
                            );
                        }

                        // 5. Push Config
                        $service->updateIngressRules($tunnel);

                        \Filament\Notifications\Notification::make()
                            ->title('Setup Complete')
                            ->body("Tunnel {$tunnel->name} created and configured!")
                            ->success()
                            ->persistent()
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Wizard Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            CreateAction::make()
                ->createAnother(false)
                ->before(function (CreateAction $action, array $data) {
                    if (($data['connection_status'] ?? null) !== 'success') {
                        $data['connection_status'] = 'error';
                        $data['connection_message'] = 'Please test the connection before creating.';

                        $action->getLivewire()->getMountedActionForm()->fill($data);

                        $action->halt();
                    }
                }),
        ];
    }
}
