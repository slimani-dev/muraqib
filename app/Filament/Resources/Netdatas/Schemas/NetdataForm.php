<?php

namespace App\Filament\Resources\Netdatas\Schemas;

use App\Models\CloudflareDomain;
use App\Models\CloudflareTunnel;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class NetdataForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->label('Service Name')
                    ->required()
                    ->maxLength(255),

                Select::make('cloudflare_ingress_rule_id')
                    ->label('Ingress Rule')
                    ->relationship('ingressRule', 'hostname')
                    ->options(function (Get $get) {
                        $query = \App\Models\CloudflareIngressRule::query()
                            ->whereNotNull('hostname'); // Ensure label is not null
                        if ($tunnelId = $get('cloudflare_tunnel_id')) {
                            $query->where('cloudflare_tunnel_id', $tunnelId);
                        }

                        return $query->pluck('hostname', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Select::make('cloudflare_tunnel_id')
                            ->label('Tunnel')
                            ->options(CloudflareTunnel::query()->pluck('name', 'id'))
                            ->default(fn (Get $get) => $get('cloudflare_tunnel_id')) // Inherit from main form
                            ->required(),
                        TextInput::make('hostname')
                            ->placeholder('sub.example.com')
                            ->required(),
                        TextInput::make('path')
                            ->placeholder('/msg'),
                        TextInput::make('service')
                            ->label('Service URL')
                            ->placeholder('http://localhost:19999')
                            ->required()
                            ->default('http://localhost:19999'),
                        \Filament\Schemas\Components\Section::make('Origin Request Settings')
                            ->schema([
                                \Filament\Forms\Components\Checkbox::make('noTLSVerify')
                                    ->label('No TLS Verify'),
                                TextInput::make('httpHostHeader'),
                                TextInput::make('originServerName'),
                            ])
                            ->statePath('origin_request')
                            ->collapsed(),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $rule = \App\Models\CloudflareIngressRule::create($data);

                        // Trigger Cloudflare Sync
                        try {
                            $tunnel = $rule->tunnel;
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);

                            // 1. Push Rules
                            $service->updateIngressRules($tunnel);
                            \Filament\Notifications\Notification::make()->title('Rules Pushed')->success()->send();

                            // 2. Publish DNS (optional, but good for Netdata)
                            if (! empty($rule->hostname) && $rule->hostname !== '*') {
                                $account = $tunnel->cloudflare;
                                if ($account) {
                                    $matchedDomain = $account->domains->first(function ($domain) use ($rule) {
                                        return str_ends_with($rule->hostname, $domain->name);
                                    });

                                    if ($matchedDomain) {
                                        $service->ensureCnameRecord($matchedDomain, $rule->hostname, "{$tunnel->tunnel_id}.cfargotunnel.com");
                                        \Filament\Notifications\Notification::make()->title('DNS Published')->success()->send();
                                    }
                                }
                            }

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()->title('Sync Failed')->body($e->getMessage())->warning()->send();
                        }

                        return $rule->getKey();
                    })
                    ->suffixAction(
                        Action::make('edit_ingress_rule')
                            ->icon('heroicon-m-pencil-square')
                            ->form(function (Get $get) {
                                return [
                                    Select::make('cloudflare_tunnel_id')
                                        ->label('Tunnel')
                                        ->options(CloudflareTunnel::query()->pluck('name', 'id'))
                                        ->required(),
                                    TextInput::make('hostname')
                                        ->placeholder('sub.example.com')
                                        ->required(),
                                    TextInput::make('path')
                                        ->placeholder('/msg'),
                                    TextInput::make('service')
                                        ->label('Service URL')
                                        ->placeholder('http://localhost:19999')
                                        ->required(),
                                    \Filament\Schemas\Components\Section::make('Origin Request Settings')
                                        ->schema([
                                            \Filament\Forms\Components\Checkbox::make('noTLSVerify')
                                                ->label('No TLS Verify'),
                                            TextInput::make('httpHostHeader'),
                                            TextInput::make('originServerName'),
                                        ])
                                        ->statePath('origin_request')
                                        ->collapsed(),
                                ];
                            })
                            ->fillForm(function (Get $get) {
                                $ruleId = $get('cloudflare_ingress_rule_id');
                                if (! $ruleId) {
                                    return [];
                                }

                                return \App\Models\CloudflareIngressRule::find($ruleId)?->toArray() ?? [];
                            })
                            ->action(function (array $data, Get $get) {
                                $ruleId = $get('cloudflare_ingress_rule_id');
                                if (! $ruleId) {
                                    return;
                                }

                                $rule = \App\Models\CloudflareIngressRule::find($ruleId);
                                if (! $rule) {
                                    return;
                                }

                                $rule->update($data);

                                // Trigger Cloudflare Sync
                                try {
                                    $tunnel = $rule->tunnel;
                                    $service = app(\App\Services\Cloudflare\CloudflareService::class);

                                    // 1. Push Rules
                                    $service->updateIngressRules($tunnel);
                                    \Filament\Notifications\Notification::make()->title('Rules Updated & Pushed')->success()->send();

                                    // 2. Publish DNS (optional)
                                    if (! empty($rule->hostname) && $rule->hostname !== '*') {
                                        $account = $tunnel->cloudflare;
                                        if ($account) {
                                            $matchedDomain = $account->domains->first(function ($domain) use ($rule) {
                                                return str_ends_with($rule->hostname, $domain->name);
                                            });

                                            if ($matchedDomain) {
                                                $service->ensureCnameRecord($matchedDomain, $rule->hostname, "{$tunnel->tunnel_id}.cfargotunnel.com");
                                            }
                                        }
                                    }
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()->title('Sync Failed')->body($e->getMessage())->warning()->send();
                                }
                            })
                            ->visible(fn (Get $get) => filled($get('cloudflare_ingress_rule_id')))
                    ),

                Select::make('cloudflare_access_id')
                    ->label('Access Policy')
                    ->relationship('access', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        Select::make('cloudflare_domain_id')
                            ->label('Cloudflare Zone')
                            ->options(CloudflareDomain::query()->pluck('name', 'id'))
                            ->required()
                            ->default(function (Get $get) {
                                // Try to infer from Ingress Rule
                                $ingressId = $get('cloudflare_ingress_rule_id');
                                if ($ingressId) {
                                    $rule = \App\Models\CloudflareIngressRule::find($ingressId);
                                    if ($rule && $rule->hostname) {
                                        // Extract domain part... crude but might work if we match end of string
                                        // Better: Find matching domain in DB
                                        $matched = CloudflareDomain::all()->first(fn ($d) => str_ends_with($rule->hostname, $d->name));

                                        return $matched?->id;
                                    }
                                }

                                return null;
                            }),
                        TextInput::make('hostname')
                            ->label('Full Hostname')
                            ->required()
                            ->default(function (Get $get) {
                                $ingressId = $get('cloudflare_ingress_rule_id');
                                if ($ingressId) {
                                    $rule = \App\Models\CloudflareIngressRule::find($ingressId);

                                    return $rule?->hostname;
                                }

                                return null;
                            }),
                    ])
                    ->createOptionUsing(function (array $data) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        $domain = CloudflareDomain::findOrFail($data['cloudflare_domain_id']);

                        try {
                            $access = $service->protectSubdomain($domain, $data['hostname']);

                            \Filament\Notifications\Notification::make()
                                ->title('Protection Enabled')
                                ->body("Protected {$access->name}")
                                ->success()
                                ->send();

                            return $access->id;
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Protection Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            return null;
                        }
                    }),
            ]);
    }
}
