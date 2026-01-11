<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TunnelsRelationManager extends RelationManager
{
    protected static string $relationship = 'tunnels';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tunnel Configuration')
                    ->tabs([
                        Tab::make('General')
                            ->icon('mdi-information')
                            ->schema([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('description')
                                    ->maxLength(255),
                                TextInput::make('tunnel_id')
                                    ->label('Tunnel UUID')
                                    ->readOnly()
                                    ->hiddenOn('create'),
                                TextInput::make('token')
                                    ->label('Tunnel Token')
                                    ->password()
                                    ->revealable()
                                    ->readOnly()
                                    ->hiddenOn('create')
                                    ->suffixAction(
                                        Action::make('fetchToken')
                                            ->icon('mdi-key')
                                            ->label('Fetch Token')
                                            ->tooltip('Fetch existing token from Cloudflare')
                                            ->visible(fn ($record) => $record?->tunnel_id)
                                            ->action(function ($record, $state, $set) {
                                                try {
                                                    $service = app(\App\Services\Cloudflare\CloudflareService::class);
                                                    $token = $service->getTunnelToken($record);

                                                    $record->update(['token' => $token]);
                                                    $set('token', $token);

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Token Fetched')
                                                        ->success()
                                                        ->send();
                                                } catch (\Exception $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Fetch Failed')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();
                                                }
                                            })
                                    ),
                                Toggle::make('is_active')
                                    ->label('Active Locally'),
                            ]),

                        Tab::make('Advanced')
                            ->icon('mdi-cog')
                            ->schema([
                                Select::make('protocol')
                                    ->label('Tunnel Protocol')
                                    ->options([
                                        'auto' => 'Auto',
                                        'http2' => 'HTTP/2',
                                        'quic' => 'QUIC',
                                    ])
                                    ->default('auto'),
                                Grid::make(3)
                                    ->schema([
                                        Select::make('loglevel')
                                            ->options([
                                                'debug' => 'Debug',
                                                'info' => 'Info',
                                                'warn' => 'Warn',
                                                'error' => 'Error',
                                                'fatal' => 'Fatal',
                                            ])
                                            ->default('info'),
                                        Select::make('transport_loglevel')
                                            ->options([
                                                'debug' => 'Debug',
                                                'info' => 'Info',
                                                'warn' => 'Warn',
                                                'error' => 'Error',
                                                'fatal' => 'Fatal',
                                            ])
                                            ->default('warn'),
                                    ]),
                                Section::make('DNS Proxy')
                                    ->schema([
                                        Toggle::make('proxy_dns')
                                            ->label('Enable DNS Proxy')
                                            ->live(),
                                        TextInput::make('proxy_dns_port')
                                            ->numeric()
                                            ->default(53)
                                            ->visible(fn ($get) => $get('proxy_dns')),
                                        TagsInput::make('proxy_dns_upstream')
                                            ->label('Upstream DNS Servers')
                                            ->placeholder('Add IP/URL')
                                            ->visible(fn ($get) => $get('proxy_dns')),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->description(fn ($record) => $record->tunnel_id),
                TextColumn::make('status')
                    ->badge()
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString(
                        '<span class="text-xs text-gray-400">' . 
                        ($record->status_checked_at ? $record->status_checked_at->diffForHumans(short: true) : 'Not checked') . 
                        '</span>'
                    )),
                TextColumn::make('client_version')
                    ->label('Version')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return 'Unknown';
                        }

                        // Get latest version from cache or fetch
                        $latestVersion = Cache::remember('cloudflared_latest_version', 3600, function () {
                            try {
                                $response = Http::timeout(5)->get('https://api.github.com/repos/cloudflare/cloudflared/releases/latest');
                                if ($response->successful()) {
                                    $tag = $response->json('tag_name');

                                    return ltrim($tag, 'v');
                                }
                            } catch (\Exception $e) {
                            }

                            return null;
                        });

                        if (! $latestVersion) {
                            return $state;
                        }

                        // Compare versions
                        $current = ltrim($state, 'v');
                        if (version_compare($current, $latestVersion, '>=')) {
                            return $state.' ✓';
                        }

                        return $state.' ⚠️';
                    })
                    ->description(function ($state) {
                        if (! $state) {
                            return null;
                        }

                        $latestVersion = Cache::get('cloudflared_latest_version');
                        if (! $latestVersion) {
                            return null;
                        }

                        $current = ltrim($state, 'v');
                        if (version_compare($current, $latestVersion, '>=')) {
                            $text = 'Up to date';
                        } else {
                            $text = 'Update available: '.$latestVersion;
                        }

                        return new \Illuminate\Support\HtmlString('<span class="text-xs text-gray-400">'.$text.'</span>');
                    })
                    ->color(function ($state) {
                        if (! $state) {
                            return 'gray';
                        }

                        $latestVersion = Cache::get('cloudflared_latest_version');
                        if (! $latestVersion) {
                            return 'gray';
                        }

                        $current = ltrim($state, 'v');
                        if (version_compare($current, $latestVersion, '>=')) {
                            return 'success';
                        }

                        return 'warning';
                    })
                    ->toggleable(),
                TextColumn::make('conns_active_at')
                    ->label('Active Since')
                    ->description(fn ($record) => new \Illuminate\Support\HtmlString(
                        '<span class="text-xs text-gray-400">'.
                        ($record->status ? ucfirst($record->status->value) : 'Unknown').
                        '</span>'
                    ))
                    ->dateTime()
                    ->since()
                    ->sortable()
                    ->toggleable(),
                \Filament\Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Tunnel')
                    ->slideOver(false)
                    ->modalWidth('7xl')
                    ->steps(\App\Filament\Resources\Cloudflares\Schemas\TunnelWizardForm::getSteps())
                    ->action(function (array $data, $livewire) {
                        $account = $livewire->ownerRecord;
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        try {
                            // 1. Create tunnel locally with data from step 1
                            $tunnelName = $data['name'] ?? null;

                            // If name is missing (e.g., existing tunnel mode), fetch it from Cloudflare
                            if (! $tunnelName && isset($data['tunnel_id_created'])) {
                                try {
                                    $service = app(\App\Services\Cloudflare\CloudflareService::class);
                                    $tunnels = $service->listTunnels($account);
                                    $matchedTunnel = collect($tunnels)->firstWhere('id', $data['tunnel_id_created']);
                                    $tunnelName = $matchedTunnel['name'] ?? 'tunnel-'.substr($data['tunnel_id_created'], 0, 8);
                                } catch (\Exception $e) {
                                    $tunnelName = 'tunnel-'.substr($data['tunnel_id_created'], 0, 8);
                                }
                            }

                            $tunnel = $account->tunnels()->create([
                                'tunnel_id' => $data['tunnel_id_created'],
                                'name' => $tunnelName,
                                'description' => $data['description'] ?? null,
                                'token' => $data['tunnel_token_created'],
                                'status' => 'inactive',
                                'is_active' => true,
                            ]);

                            // 2. Sync tunnel status
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
                                // Ignore status sync errors
                            }

                            // 3. Create ingress rules and DNS
                            foreach ($data['ingress_rules'] as $ruleData) {
                                if (empty($ruleData['hostname'])) {
                                    continue;
                                }

                                // Create local ingress rule
                                $tunnel->ingressRules()->create([
                                    'hostname' => $ruleData['hostname'],
                                    'service' => $ruleData['service'],
                                    'path' => $ruleData['path'] ?? null,
                                    'origin_request' => $ruleData['origin_request'] ?? null,
                                    'is_catch_all' => false,
                                ]);

                                // Create DNS record
                                if (! empty($ruleData['cloudflare_domain_id'])) {
                                    $domain = \App\Models\CloudflareDomain::find($ruleData['cloudflare_domain_id']);
                                    if ($domain) {
                                        try {
                                            $service->ensureCnameRecord(
                                                $domain,
                                                $ruleData['hostname'],
                                                "{$tunnel->tunnel_id}.cfargotunnel.com"
                                            );
                                        } catch (\Exception $e) {
                                            // Ignore DNS errors, can be done manually
                                        }
                                    }
                                }
                            }

                            // 4. Push configuration to Cloudflare
                            try {
                                $service->updateIngressRules($tunnel);
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Tunnel Created, Config Push Failed')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->send();
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Tunnel Created Successfully')
                                ->body("Tunnel {$tunnel->name} is ready!")
                                ->success()
                                ->send();

                            return $tunnel;
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tunnel Creation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw $e;
                        }
                    }),
                Action::make('attach_existing')
                    ->label('Attach Tunnel')
                    ->icon('mdi-link')
                    ->schema([
                        Select::make('tunnel_id')
                            ->label('Select Tunnel')
                            ->searchable()
                            ->options(function ($livewire) {
                                $account = $livewire->ownerRecord;
                                if (! $account->api_token) {
                                    return [];
                                }

                                try {
                                    $service = app(\App\Services\Cloudflare\CloudflareService::class);
                                    $tunnels = $service->listTunnels($account);

                                    return collect($tunnels)
                                        ->mapWithKeys(function ($tunnel) {
                                            $name = $tunnel['name'] ?? 'Unknown';
                                            $id = $tunnel['id'] ?? 'N/A';
                                            $status = $tunnel['status'] ?? 'unknown';

                                            return [$id => "$name - $status ($id)"];
                                        });
                                } catch (\Exception $e) {
                                    return [];
                                }
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        $account = $livewire->ownerRecord;
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        $tunnel = $account->tunnels()->create([
                            'tunnel_id' => $data['tunnel_id'],
                            'name' => 'Loading...',
                            'status' => 'unknown',
                            'is_active' => false,
                        ]);

                        try {
                            $details = $service->getTunnelDetails($tunnel);
                            if ($details) {
                                $tunnel->update([
                                    'name' => $details['name'],
                                    'status' => $details['status'],
                                    'is_active' => ($details['status'] === 'healthy'),
                                    'conns_active_at' => $details['conns_active_at'] ?? null,
                                    'client_version' => $details['connections'][0]['client_version'] ?? null,
                                    'status_checked_at' => now(),
                                ]);
                            }
                        } catch (\Exception $e) {
                            // Ignore sync error
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Tunnel Attached')
                            ->success()
                            ->send();
                    }),
                Action::make('sync_status')
                    ->label('Sync Status')
                    ->icon('mdi-refresh')
                    ->action(function ($livewire) {
                        /** @var \App\Models\Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        try {
                            $tunnels = $service->listTunnels($account);
                            $updatedCount = 0;
                            foreach ($tunnels as $remoteTunnel) {
                                $localTunnel = $account->tunnels()->where('tunnel_id', $remoteTunnel['id'])->first();
                                if ($localTunnel) {
                                    $details = $service->getTunnelDetails($localTunnel);
                                    if ($details) {
                                        $localTunnel->update([
                                            'status' => $details['status'],
                                            'conns_active_at' => $details['conns_active_at'] ?? null,
                                            'client_version' => $details['connections'][0]['client_version'] ?? null,
                                            'is_active' => ($details['status'] === 'healthy'),
                                            'status_checked_at' => now(),
                                        ]);
                                        $updatedCount++;
                                    }
                                }
                            }
                            \Filament\Notifications\Notification::make()
                                ->title("Synced $updatedCount Tunnels")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                Action::make('sync_ingress_rules')
                    ->label('Pull Rules')
                    ->slideOver(false)
                    ->icon('mdi-cloud-download')
                    ->requiresConfirmation()
                    ->modalHeading('Sync Ingress Rules')
                    ->modalDescription('This will replace all local ingress rules for this tunnel with the configuration from Cloudflare. Are you sure?')
                    ->action(function ($record) {
                        try {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $rules_config = $service->getTunnelConfig($record);

                            if (! is_array($rules_config)) {
                                throw new \Exception('No configuration found or invalid response.');
                            }

                            // Clean existing rules
                            // Note: We use the relationship on the tunnel instance
                            // Assuming CloudflareTunnel hasMany ingressRules or we traverse via account?
                            // Checking CloudflareTunnel model...
                            // Ah, CloudflareTunnel doesn't have direct ingressRules relation in default setup usually,
                            // but IngressRulesRelationManager uses "ingressRules" on ACCOUNT via hasManyThrough.
                            // We need to delete rules where cloudflare_tunnel_id = $record->id

                            \App\Models\CloudflareIngressRule::where('cloudflare_tunnel_id', $record->id)->delete();

                            $count = 0;
                            foreach ($rules_config as $rule) {
                                $serviceName = $rule['service'] ?? null;
                                // Skip if it's just the 404 http status catch-all unless user wants it

                                \App\Models\CloudflareIngressRule::create([
                                    'cloudflare_tunnel_id' => $record->id,
                                    'hostname' => $rule['hostname'] ?? null,
                                    'path' => $rule['path'] ?? null,
                                    'service' => $serviceName,
                                    'origin_request' => isset($rule['originRequest']) ? $rule['originRequest'] : null,
                                    'is_catch_all' => $serviceName === 'http_status:404',
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Synced {$count} Rules")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->slideOver(false)
                    ->modalWidth('7xl')
                    ->steps(fn ($record) => \App\Filament\Resources\Cloudflares\Schemas\TunnelWizardForm::getSteps($record))
                    ->fillForm(function ($record) {
                        // Pre-fill form with tunnel and ingress data
                        return [
                            'name' => $record->name,
                            'description' => $record->description,
                            'tunnel_id' => $record->tunnel_id,
                            'token' => $record->token,
                        ];
                    })
                    ->action(function (array $data, $record) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        try {
                            // 1. Update tunnel metadata
                            $record->update([
                                'description' => $data['description'] ?? null,
                            ]);

                            // 2. Sync ingress rules from form
                            if (isset($data['ingress_rules'])) {
                                // Delete old rules
                                $record->ingressRules()->delete();

                                // Create new rules and DNS
                                foreach ($data['ingress_rules'] as $ruleData) {
                                    if (empty($ruleData['hostname'])) {
                                        continue;
                                    }

                                    $record->ingressRules()->create([
                                        'hostname' => $ruleData['hostname'],
                                        'service' => $ruleData['service'],
                                        'path' => $ruleData['path'] ?? null,
                                        'origin_request' => $ruleData['origin_request'] ?? null,
                                        'is_catch_all' => false,
                                    ]);

                                    // Update DNS record
                                    if (! empty($ruleData['cloudflare_domain_id'])) {
                                        $domain = \App\Models\CloudflareDomain::find($ruleData['cloudflare_domain_id']);
                                        if ($domain) {
                                            try {
                                                $service->ensureCnameRecord(
                                                    $domain,
                                                    $ruleData['hostname'],
                                                    "{$record->tunnel_id}.cfargotunnel.com"
                                                );
                                            } catch (\Exception $e) {
                                                // Ignore DNS errors
                                            }
                                        }
                                    }
                                }

                                // 3. Push updated configuration to Cloudflare
                                try {
                                    $service->updateIngressRules($record);
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Tunnel Updated, Config Push Failed')
                                        ->body($e->getMessage())
                                        ->warning()
                                        ->send();
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Tunnel Updated Successfully')
                                ->success()
                                ->send();

                            return $record;
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tunnel Update Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            throw $e;
                        }
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
