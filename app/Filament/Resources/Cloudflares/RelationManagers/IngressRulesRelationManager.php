<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

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
                //
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
                                    \Filament\Notifications\Notification::make()
                                        ->title("Pushed Rules to {$tunnel->name}")
                                        ->body('Configuration updated on Cloudflare.')
                                        ->success()
                                        ->send();
                                } else {
                                    throw new \Exception('Cloudflare API returned failure.');
                                }

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
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
                                    \Filament\Notifications\Notification::make()
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
                                    \Filament\Notifications\Notification::make()
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

                                    \Filament\Notifications\Notification::make()
                                        ->title('DNS Published')
                                        ->body("Record {$status} for {$record->hostname}")
                                        ->success()
                                        ->send();

                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Publication Error')
                                        ->body($e->getMessage())
                                        ->danger()
                                        ->send();
                                }

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Sync Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteAction::make(),
                ]),
            ])
            ->toolbarActions([
                \Filament\Actions\DeleteBulkAction::make(),
            ]);
    }
}
