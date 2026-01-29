<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class IngressRulesRelationManager extends RelationManager
{
    protected static string $relationship = 'ingressRules';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('cloudflare_tunnel_id')
                    ->label('Tunnel')
                    ->options(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        return $account->tunnels->mapWithKeys(function ($tunnel) {
                            $statusColor = $tunnel->status === \App\Enums\CloudflareStatus::Healthy ? 'text-success-600' : 'text-danger-600';
                            $html = "<div class='flex flex-col'>
                                         <span class='font-bold'>{$tunnel->name}</span>
                                         <span class='text-xs {$statusColor}'>{$tunnel->status?->getLabel()}</span>
                                      </div>";

                            return [$tunnel->id => $html];
                        });
                    })
                    ->default(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        return $account->tunnels->count() === 1 ? $account->tunnels->first()->id : null;
                    })
                    ->allowHtml()
                    ->searchable()
                    ->required(),

                Toggle::make('is_catch_all')
                    ->label('Catch-All Rule (404)')
                    ->live(),

                Grid::make(2)
                    ->visible(fn ($get) => ! $get('is_catch_all'))
                    ->schema([
                        TextInput::make('hostname')
                            ->placeholder('sub.example.com or *')
                            ->required(),
                        TextInput::make('path')
                            ->placeholder('/msg'),
                    ]),

                TextInput::make('service')
                    ->label('Service URL')
                    ->placeholder('http://localhost:8000 or http_status:404')
                    ->required(fn ($get) => ! $get('is_catch_all'))
                    ->default('http://localhost:8000')
                    ->visible(fn ($get) => ! $get('is_catch_all')),

                Section::make('Origin Request Settings')
                    ->schema([
                        Checkbox::make('noTLSVerify')
                            ->label('No TLS Verify')
                            ->inline(),
                        TextInput::make('httpHostHeader')
                            ->label('HTTP Host Header'),
                        TextInput::make('originServerName')
                            ->label('Origin Server Name'),
                    ])
                    ->statePath('origin_request')
                    ->collapsed()
                    ->visible(fn ($get) => ! $get('is_catch_all')),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('hostname')
            ->columns([
                TextColumn::make('hostname')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->path),
                TextColumn::make('service')
                    ->label('Service')
                    ->limit(30),
                TextColumn::make('tunnel.name')
                    ->label('Tunnel')
                    ->badge()
                    ->color(fn ($record) => $record->tunnel?->status?->getColor() ?? 'gray'),
                \Filament\Tables\Columns\IconColumn::make('is_catch_all')
                    ->boolean()
                    ->label('Catch-All'),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('cloudflare_tunnel_id')
                    ->label('Tunnel')
                    ->options(function () {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        return $account->tunnels->pluck('name', 'id');
                    }),
                \Filament\Tables\Filters\SelectFilter::make('zone')
                    ->label('Zone')
                    ->options(function () {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        return $account->domains->pluck('name', 'name');
                    })
                    ->query(function ($query, array $data) {
                        if (! empty($data['value'])) {
                            return $query->where('hostname', 'like', '%'.$data['value']);
                        }

                        return $query;
                    }),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New Rule')
                    ->using(function (array $data, string $model, $livewire) {
                        if (! empty($data['is_catch_all']) && empty($data['service'])) {
                            $data['service'] = 'http_status:404';
                        }

                        return \App\Models\CloudflareIngressRule::create($data);
                    })
                    ->after(function ($record) {
                        $tunnel = $record->tunnel;
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        // 1. Push Rules
                        try {
                            $service->updateIngressRules($tunnel);
                            \Filament\Notifications\Notification::make()->title('Rules Pushed')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Push Failed')->body($e->getMessage())->warning()->send();
                        }

                        // 2. Publish DNS
                        if (empty($record->hostname) || $record->hostname === '*') {
                            return;
                        }

                        $account = $tunnel->cloudflare;
                        if (! $account) {
                            return;
                        }

                        $matchedDomain = $account->domains->first(function ($domain) use ($record) {
                            return str_ends_with($record->hostname, $domain->name);
                        });

                        if ($matchedDomain) {
                            try {
                                $service->ensureCnameRecord($matchedDomain, $record->hostname, "{$tunnel->tunnel_id}.cfargotunnel.com");
                                \Filament\Notifications\Notification::make()->title('DNS Published')->success()->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->title('DNS Publish Failed')->body($e->getMessage())->warning()->send();
                            }
                        }
                    }),
                Action::make('sync_ingress_rules')
                    ->slideOver(false)
                    ->label('Pull Rules')
                    ->icon('mdi-cloud-download')
                    ->schema([
                        Select::make('tunnel_id')
                            ->label('Select Tunnel')
                            ->options(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->tunnels->pluck('name', 'id');
                            })
                            ->default(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->tunnels->count() === 1 ? $account->tunnels->first()->id : null;
                            })
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        try {
                            $tunnel = \App\Models\CloudflareTunnel::findOrFail($data['tunnel_id']);
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);

                            $rules_config = $service->getTunnelConfig($tunnel);

                            if (! is_array($rules_config)) {
                                throw new \Exception('No configuration found or invalid response.');
                            }

                            // Clean existing rules for this tunnel
                            \App\Models\CloudflareIngressRule::where('cloudflare_tunnel_id', $tunnel->id)->delete();

                            $count = 0;
                            foreach ($rules_config as $rule) {
                                $serviceName = $rule['service'] ?? null;

                                \App\Models\CloudflareIngressRule::create([
                                    'cloudflare_tunnel_id' => $tunnel->id,
                                    'hostname' => $rule['hostname'] ?? null,
                                    'path' => $rule['path'] ?? null,
                                    'service' => $serviceName,
                                    'origin_request' => isset($rule['originRequest']) ? $rule['originRequest'] : null,
                                    'is_catch_all' => $serviceName === 'http_status:404',
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Synced {$count} Rules for {$tunnel->name}")
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
                \Filament\Actions\Action::make('open_url')
                    ->label('Open URL')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => "https://{$record->hostname}")
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => ! empty($record->hostname) && $record->hostname !== '*'),
                EditAction::make()
                    ->using(function (array $data, \App\Models\CloudflareIngressRule $record, $livewire) {
                        if (! empty($data['is_catch_all']) && empty($data['service'])) {
                            $data['service'] = 'http_status:404';
                        }
                        $record->update($data);

                        return $record;
                    })
                    ->after(function ($record) {
                        $tunnel = $record->tunnel;
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        // 1. Push Rules
                        try {
                            $service->updateIngressRules($tunnel);
                            \Filament\Notifications\Notification::make()->title('Rules Pushed')->success()->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Push Failed')->body($e->getMessage())->warning()->send();
                        }

                        // 2. Publish DNS
                        if (empty($record->hostname) || $record->hostname === '*') {
                            return;
                        }

                        $account = $tunnel->cloudflare;
                        if (! $account) {
                            return;
                        }

                        $matchedDomain = $account->domains->first(function ($domain) use ($record) {
                            return str_ends_with($record->hostname, $domain->name);
                        });

                        if ($matchedDomain) {
                            try {
                                $service->ensureCnameRecord($matchedDomain, $record->hostname, "{$tunnel->tunnel_id}.cfargotunnel.com");
                                \Filament\Notifications\Notification::make()->title('DNS Published')->success()->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()->title('DNS Publish Failed')->body($e->getMessage())->warning()->send();
                            }
                        }
                    }),
                ActionGroup::make([
                    Action::make('open_service')
                        ->label('Open Service')
                        ->icon('heroicon-o-computer-desktop')
                        ->url(fn ($record) => $record->service)
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => str_starts_with($record->service ?? '', 'http')),
                    Action::make('deploy_ingress_rules')
                        ->slideOver(false)
                        ->label('Push Rule')
                        ->icon('mdi-cloud-upload')
                        ->color('danger')
                        ->action(function ($record) {
                            try {
                                $tunnel = $record->tunnel;
                                $service = app(\App\Services\Cloudflare\CloudflareService::class);

                                $success = $service->updateIngressRules($tunnel);

                                if ($success) {
                                    Notification::make()
                                        ->title("Pushed Rules to {$tunnel->name}")
                                        ->body('Configuration updated on Cloudflare.')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Cloudflare API returned failure.');
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Push Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_from_ingress')
                        ->slideOver(false)
                        ->label('Publish to DNS')
                        ->icon('mdi-earth-arrow-right')
                        ->color('warning')
                        ->action(function ($record, $livewire) {
                            try {
                                /** @var \App\Models\CloudflareTunnel $tunnel */
                                $tunnel = $record->tunnel;

                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();
                                $domains = $account->domains;

                                $service = app(\App\Services\Cloudflare\CloudflareService::class);
                                $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 0];

                                // Act on just this single rule ($record)
                                if (empty($record->hostname) || $record->hostname === '*') {
                                    Notification::make()
                                        ->title('Skipped')
                                        ->body('Hostname is empty or wildcard.')
                                        ->warning()
                                        ->send();

                                    return;
                                }

                                // Find matching zone
                                $matchedDomain = $domains->first(function ($domain) use ($record) {
                                    return str_ends_with($record->hostname, $domain->name);
                                });

                                if (! $matchedDomain) {
                                    Notification::make()
                                        ->title('No Zone Found')
                                        ->body("No matching zone found for {$record->hostname}")
                                        ->danger()
                                        ->send();

                                    return;
                                }

                                try {
                                    $status = $service->ensureCnameRecord(
                                        $matchedDomain,
                                        $record->hostname,
                                        "{$tunnel->tunnel_id}.cfargotunnel.com"
                                    );

                                    Notification::make()
                                        ->title('DNS Published')
                                        ->body("Record {$status} for {$record->hostname}")
                                        ->success()
                                        ->send();

                                } catch (\Exception $e) {
                                    Notification::make()
                                        ->title('Publication Error')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('migrate_tunnel')
                        ->label('Migrate Tunnel')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('info')
                        ->form([
                            Select::make('target_tunnel_id')
                                ->label('Target Tunnel')
                                ->options(function () {
                                    /** @var Cloudflare $account */
                                    $account = $this->getOwnerRecord();

                                    return $account->tunnels->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (\App\Models\CloudflareIngressRule $record, array $data) {
                            $targetTunnelId = $data['target_tunnel_id'];
                            if ($record->cloudflare_tunnel_id == $targetTunnelId) {
                                Notification::make()->title('Skipped')->body('Source and Target are the same.')->warning()->send();

                                return;
                            }

                            $sourceTunnel = $record->tunnel;
                            $targetTunnel = \App\Models\CloudflareTunnel::find($targetTunnelId);

                            if (! $targetTunnel) {
                                Notification::make()->title('Error')->body('Target tunnel not found.')->danger()->send();

                                return;
                            }

                            // 1. Update Record
                            $record->update(['cloudflare_tunnel_id' => $targetTunnel->id]);

                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $errors = [];

                            // 2. Push to Target
                            try {
                                $service->updateIngressRules($targetTunnel);
                            } catch (\Exception $e) {
                                $errors[] = "Target Config Push Failed: {$e->getMessage()}";
                            }

                            // 3. Push to Source
                            try {
                                $service->updateIngressRules($sourceTunnel);
                            } catch (\Exception $e) {
                                $errors[] = "Source Config Push Failed: {$e->getMessage()}";
                            }

                            // 4. Update DNS
                            if (! empty($record->hostname) && $record->hostname !== '*') {
                                $account = $targetTunnel->cloudflare;
                                $matchedDomain = $account->domains->first(function ($domain) use ($record) {
                                    return str_ends_with($record->hostname, $domain->name);
                                });

                                if ($matchedDomain) {
                                    try {
                                        $service->ensureCnameRecord($matchedDomain, $record->hostname, "{$targetTunnel->tunnel_id}.cfargotunnel.com");
                                    } catch (\Exception $e) {
                                        $errors[] = "DNS Update Failed: {$e->getMessage()}";
                                    }
                                }
                            }

                            if (count($errors) > 0) {
                                Notification::make()
                                    ->title('Migration Completed with Errors')
                                    ->body(implode("\n", $errors))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Tunnel Migrated')
                                    ->success()
                                    ->send();
                            }
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    BulkAction::make('migrate_bulk')
                        ->label('Migrate Tunnels')
                        ->icon('heroicon-o-arrow-path-rounded-square')
                        ->color('info')
                        ->form([
                            Select::make('target_tunnel_id')
                                ->label('Target Tunnel')
                                ->options(function () {
                                    /** @var Cloudflare $account */
                                    $account = $this->getOwnerRecord();

                                    return $account->tunnels->pluck('name', 'id');
                                })
                                ->required()
                                ->searchable()
                                ->preload(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $targetTunnelId = $data['target_tunnel_id'];
                            $targetTunnel = \App\Models\CloudflareTunnel::find($targetTunnelId);

                            if (! $targetTunnel) {
                                Notification::make()->title('Error')->body('Target tunnel not found.')->danger()->send();

                                return;
                            }

                            $sourceTunnels = collect();
                            $migratedCount = 0;
                            $errors = [];

                            foreach ($records as $record) {
                                if ($record->cloudflare_tunnel_id == $targetTunnelId) {
                                    continue;
                                }

                                $sourceTunnels->push($record->tunnel);
                                $record->update(['cloudflare_tunnel_id' => $targetTunnel->id]);
                                $migratedCount++;
                            }

                            if ($migratedCount === 0) {
                                Notification::make()->title('Nothing to Migrate')->body('Selected records are already on the target tunnel.')->warning()->send();

                                return;
                            }

                            $service = app(\App\Services\Cloudflare\CloudflareService::class);

                            // 1. Push to Target
                            try {
                                $service->updateIngressRules($targetTunnel);
                            } catch (\Exception $e) {
                                $errors[] = "Target Config Push Failed: {$e->getMessage()}";
                            }

                            // 2. Push to Sources
                            foreach ($sourceTunnels->unique('id') as $sourceTunnel) {
                                try {
                                    $service->updateIngressRules($sourceTunnel);
                                } catch (\Exception $e) {
                                    $errors[] = "Source ({$sourceTunnel->name}) Push Failed: {$e->getMessage()}";
                                }
                            }

                            // 3. Update DNS for all migrated records
                            foreach ($records as $record) {
                                if (empty($record->hostname) || $record->hostname === '*') {
                                    continue;
                                }

                                /** @var \App\Models\Cloudflare $account */
                                $account = $targetTunnel->cloudflare;
                                $matchedDomain = $account->domains->first(function ($domain) use ($record) {
                                    return str_ends_with($record->hostname, $domain->name);
                                });

                                if ($matchedDomain) {
                                    try {
                                        $service->ensureCnameRecord($matchedDomain, $record->hostname, "{$targetTunnel->tunnel_id}.cfargotunnel.com");
                                    } catch (\Exception $e) {
                                        $errors[] = "DNS ({$record->hostname}) Failed: {$e->getMessage()}";
                                    }
                                }
                            }

                            if (count($errors) > 0) {
                                Notification::make()
                                    ->title("Migrated {$migratedCount} Rules with Errors")
                                    ->body(implode("\n", $errors))
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("Migrated {$migratedCount} Rules")
                                    ->success()
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
