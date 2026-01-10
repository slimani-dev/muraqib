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
                            ->icon('heroicon-o-information-circle')
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
                                            ->icon('heroicon-o-key')
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
                            ->icon('heroicon-o-cog')
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
                    ->badge(),
                TextColumn::make('client_version')
                    ->label('Version')
                    ->toggleable(),
                TextColumn::make('conns_active_at')
                    ->label('Active Since')
                    ->dateTime()
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
                    ->using(function (array $data, string $model, $livewire) {
                        $account = $livewire->ownerRecord;
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        try {
                            // 1. Create remote tunnel
                            $remoteTunnel = $service->findOrCreateTunnel($account, $data['name']);

                            $data['tunnel_id'] = $remoteTunnel['id'];
                            $data['status'] = 'inactive';

                            // 2. Create local record
                            $tunnel = $account->tunnels()->create($data);

                            // 3. Fetch token immediately
                            try {
                                $token = $service->getTunnelToken($tunnel);
                                $tunnel->update(['token' => $token]);
                            } catch (\Exception $e) {
                                // Token fetch failed, user can retry manually
                                \Filament\Notifications\Notification::make()
                                    ->title('Tunnel Created but Token Fetch Failed')
                                    ->body($e->getMessage())
                                    ->warning()
                                    ->send();
                            }

                            return $tunnel;
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Tunnel Creation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            // Re-throw to prevent modal closing if possible, or just halt
                            throw $e;
                        }
                    }),
                Action::make('attach_existing')
                    ->label('Attach Tunnel')
                    ->icon('heroicon-o-link')
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
                                    'client_version' => $details['client_version'] ?? null,
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
                    ->icon('heroicon-o-arrow-path')
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
                                            'client_version' => $details['client_version'] ?? null,
                                            'is_active' => ($details['status'] === 'healthy'),
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
                    ->icon('heroicon-o-arrow-down-tray')
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
