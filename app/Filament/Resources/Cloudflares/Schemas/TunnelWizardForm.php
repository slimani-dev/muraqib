<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use App\Models\CloudflareTunnel;
use App\Services\Cloudflare\CloudflareService;
use CodeWithDennis\SimpleAlert\Components\SimpleAlert;
use Filament\Actions;
use Filament\Forms;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components;
use Filament\Support\Exceptions\Halt;
use LaraZeus\TorchFilament\Infolists\TorchEntry;
use Webbingbrasil\FilamentCopyActions\Actions\CopyAction;

class TunnelWizardForm
{
    public static function getSteps($record = null): array
    {
        $isEdit = $record !== null;

        return [
            // Step 1: Tunnel Configuration
            Components\Wizard\Step::make('Tunnel')
                ->description($isEdit ? 'Tunnel Information' : 'Create New Tunnel')
                ->schema([
                    SimpleAlert::make('tunnel_info')
                        ->info()
                        ->title('Cloudflare Tunnel')
                        ->description('A tunnel creates a private, encrypted connection between your infrastructure and Cloudflare.')
                        ->icon('heroicon-m-shield-check'),

                    Forms\Components\Radio::make('tunnel_mode')
                        ->label('Tunnel Action')
                        ->options(['create' => 'Create New Tunnel', 'existing' => 'Use Existing Tunnel'])
                        ->default('create')
                        ->live()
                        ->visible(! $isEdit)
                        ->afterStateUpdated(function ($state, $set) {
                            // Clear fields when switching modes
                            if ($state === 'create') {
                                $set('existing_tunnel_id', null);
                            } else {
                                $set('name', null);
                            }
                        }),

                    Forms\Components\TextInput::make('name')
                        ->label('Tunnel Name')
                        ->required(fn ($get) => ! $isEdit && $get('tunnel_mode') === 'create')
                        ->maxLength(255)
                        ->default($record?->name ?? 'muraqib-tunnel')
                        ->helperText('A descriptive name for your tunnel. Use alphanumeric characters and hyphens.')
                        ->readOnly($isEdit)
                        ->visible(fn ($get) => $isEdit || $get('tunnel_mode') === 'create'),

                    Forms\Components\Select::make('existing_tunnel_id')
                        ->label('Select Existing Tunnel')
                        ->options(function ($livewire) {
                            $account = $livewire->ownerRecord;
                            $service = app(CloudflareService::class);

                            try {
                                $tunnels = $service->listTunnels($account);
                                $options = [];
                                foreach ($tunnels as $tunnel) {
                                    $options[$tunnel['id']] = $tunnel['name'];
                                }

                                return $options;
                            } catch (\Exception $e) {
                                return [];
                            }
                        })
                        ->searchable()
                        ->required(fn ($get) => ! $isEdit && $get('tunnel_mode') === 'existing')
                        ->visible(fn ($get) => ! $isEdit && $get('tunnel_mode') === 'existing')
                        ->helperText('Selecting an existing tunnel will attach it to this account.')
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $livewire) {
                            if (! $state) {
                                return;
                            }

                            $account = $livewire->ownerRecord;
                            $service = app(CloudflareService::class);

                            try {
                                $tunnels = $service->listTunnels($account);
                                $selectedTunnel = collect($tunnels)->firstWhere('id', $state);

                                if ($selectedTunnel) {
                                    $set('name', $selectedTunnel['name']);
                                    $set('tunnel_id_created', $selectedTunnel['id']);

                                    // Fetch token
                                    $mockTunnel = new CloudflareTunnel(['tunnel_id' => $selectedTunnel['id']]);
                                    $mockTunnel->setRelation('cloudflare', $account);
                                    $tunnelToken = $service->getTunnelToken($mockTunnel);
                                    $set('tunnel_token_created', $tunnelToken);
                                }
                            } catch (\Exception $e) {
                            }
                        }),

                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->default($record?->description),

                    Forms\Components\TextInput::make('tunnel_id')
                        ->label('Tunnel ID')
                        ->readOnly()
                        ->visible($isEdit)
                        ->default($record?->tunnel_id),

                    Forms\Components\Hidden::make('tunnel_id_created'),
                    Forms\Components\Hidden::make('tunnel_token_created'),
                    Forms\Components\Hidden::make('tunnel_mode')->default('create'),
                ])
                ->afterValidation(function ($state, $set, $get, $livewire) use ($isEdit, $record) {
                    if ($isEdit) {
                        // For edit, just update metadata if changed
                        if ($record && ($record->description !== $state['description'])) {
                            $record->update(['description' => $state['description']]);
                        }

                        return;
                    }

                    // For create: Check mode
                    $mode = $get('tunnel_mode') ?? 'create';

                    if ($mode === 'existing') {
                        // Token and ID already set by afterStateUpdated
                        return;
                    }

                    // For create mode: Create tunnel on Cloudflare
                    $account = $livewire->ownerRecord;
                    $service = app(CloudflareService::class);

                    try {
                        $remoteTunnel = $service->findOrCreateTunnel($account, $state['name']);
                        $set('tunnel_id_created', $remoteTunnel['id']);

                        // Fetch token
                        $mockTunnel = new CloudflareTunnel(['tunnel_id' => $remoteTunnel['id']]);
                        $mockTunnel->setRelation('cloudflare', $account);
                        $tunnelToken = $service->getTunnelToken($mockTunnel);
                        $set('tunnel_token_created', $tunnelToken);
                    } catch (\Exception $e) {
                        throw new Halt;
                    }
                }),

            // Step 2: Installation & Connection
            Components\Wizard\Step::make('Installation')
                ->description('Install & Connect')
                ->schema([
                    SimpleAlert::make('install_info')
                        ->info()
                        ->title('Deploy the Agent')
                        ->description('Run one of the commands below on your server to connect it to this tunnel.')
                        ->icon('heroicon-m-rocket-launch'),

                    Components\Tabs::make('Installation Method')
                        ->tabs(function ($get, $record) use ($isEdit) {
                            $tunnelToken = $isEdit ? $record?->token : $get('tunnel_token_created');
                            $tunnelId = $isEdit ? $record?->tunnel_id : $get('tunnel_id_created');

                            if (! $tunnelToken || ! $tunnelId) {
                                return [];
                            }

                            $yaml = "image: cloudflare/cloudflared:latest\ncommand: tunnel run\nenvironment:\n  - TUNNEL_TOKEN={$tunnelToken}\nrestart: always";
                            $dotEnv = "TUNNEL_TOKEN={$tunnelToken}";
                            $dockerRunCommand = "docker run -d --restart always --name cloudflared -e TUNNEL_TOKEN={$tunnelToken} cloudflare/cloudflared:latest tunnel run";
                            $linuxCommand1 = 'curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb';
                            $linuxCommand2 = "sudo dpkg -i cloudflared.deb && sudo cloudflared service install {$tunnelToken}";

                            return [
                                Components\Tabs\Tab::make('Docker Compose')
                                    ->icon('mdi-docker')
                                    ->schema([
                                        TextEntry::make('docker_compose_guide')
                                            ->hiddenLabel()
                                            ->state('If you use Portainer or Docker Compose, use this configuration:'),

                                        TorchEntry::make('code')
                                            ->columnSpanFull()
                                            ->withWrapper()
                                            ->grammar('yaml')
                                            ->hintActions([CopyAction::make()->copyable($yaml)])
                                            ->state($yaml),

                                        TorchEntry::make('.env')
                                            ->columnSpanFull()
                                            ->grammar('yaml')
                                            ->hintActions([CopyAction::make()->copyable($dotEnv)])
                                            ->state($dotEnv),
                                    ]),

                                Components\Tabs\Tab::make('Docker (CLI)')
                                    ->icon('mdi-docker')
                                    ->schema([
                                        TextEntry::make('docker_cli_guide')
                                            ->hiddenLabel()
                                            ->state('Run this command to start the agent immediately:'),

                                        TorchEntry::make('docker_run_command')
                                            ->columnSpanFull()
                                            ->grammar('shell')
                                            ->hintActions([CopyAction::make()->copyable($dockerRunCommand)])
                                            ->state($dockerRunCommand),
                                    ]),

                                Components\Tabs\Tab::make('Ubuntu / Debian (CLI)')
                                    ->icon('mdi-console')
                                    ->schema([
                                        TextEntry::make('linux_guide')
                                            ->hiddenLabel()
                                            ->state('Run these commands on your Linux server:')
                                            ->helperText('.deb - amd64 / x86-64'),

                                        TorchEntry::make('command')
                                            ->columnSpanFull()
                                            ->grammar('shell')
                                            ->hintActions([CopyAction::make()->copyable($linuxCommand1)])
                                            ->state($linuxCommand1),

                                        TorchEntry::make('command2')
                                            ->columnSpanFull()
                                            ->grammar('shell')
                                            ->hintActions([CopyAction::make()->copyable($linuxCommand2)])
                                            ->state($linuxCommand2),
                                    ]),
                            ];
                        }),

                    SimpleAlert::make('tunnel_status_alert')
                        ->danger()
                        ->title('Tunnel Disconnected')
                        ->description('Your tunnel is not yet connected to Cloudflare. Please run the installer above.')
                        ->icon('heroicon-m-exclamation-triangle')
                        ->visible(fn ($get) => filled($get('tunnel_status')) && $get('tunnel_status') === 'down'),

                    SimpleAlert::make('connection_alert')
                        ->success()
                        ->title('Connection Status')
                        ->description(fn ($get) => $get('connection_message'))
                        ->icon('heroicon-m-check-circle')
                        ->visible(fn ($get) => $get('connection_status') === 'success'),

                    SimpleAlert::make('connection_warning_alert')
                        ->warning()
                        ->title('Connection Warning')
                        ->description(fn ($get) => $get('connection_message'))
                        ->icon('heroicon-m-exclamation-triangle')
                        ->visible(fn ($get) => $get('connection_status') === 'warning'),

                    SimpleAlert::make('connection_error_alert')
                        ->danger()
                        ->title('Connection Error')
                        ->description(fn ($get) => $get('connection_message'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn ($get) => $get('connection_status') === 'error'),

                    Components\Actions::make([
                        Actions\Action::make('checkConnection')
                            ->label('Check Connection')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->action(function ($get, $set, $livewire, $record) use ($isEdit) {
                                $tunnelId = $isEdit ? $record?->tunnel_id : $get('tunnel_id_created');
                                $account = $livewire->ownerRecord;

                                if (! $tunnelId) {
                                    $set('connection_status', 'error');
                                    $set('connection_message', 'No tunnel ID found.');
                                    $set('tunnel_status', 'down');

                                    return;
                                }

                                $service = app(CloudflareService::class);
                                $tunnel = new CloudflareTunnel(['tunnel_id' => $tunnelId]);
                                $tunnel->setRelation('cloudflare', $account);

                                try {
                                    $details = $service->getTunnelDetails($tunnel);
                                    $status = $details['status'] ?? 'down';
                                    $set('tunnel_status', $status);

                                    if ($status === 'healthy') {
                                        $set('connection_status', 'success');
                                        $set('connection_message', 'Tunnel is connected and healthy');

                                        // Populate ingress rules from tunnel config
                                        try {
                                            $ingress = $service->getTunnelConfig($tunnel);
                                            if (is_array($ingress)) {
                                                $rules = [];
                                                $domains = $account->domains;

                                                foreach ($ingress as $rule) {
                                                    if (! empty($rule['hostname'])) {
                                                        $hostname = $rule['hostname'];

                                                        // Match hostname to domain
                                                        $matchedDomainId = null;
                                                        foreach ($domains as $domain) {
                                                            if (str_ends_with($hostname, $domain->name)) {
                                                                $matchedDomainId = $domain->id;
                                                                break;
                                                            }
                                                        }

                                                        $item = [
                                                            'hostname' => $hostname,
                                                            'service' => $rule['service'] ?? '',
                                                            'cloudflare_domain_id' => $matchedDomainId,
                                                        ];

                                                        if (isset($rule['originRequest'])) {
                                                            $item['origin_request'] = $rule['originRequest'];
                                                        }

                                                        $rules[] = $item;
                                                    }
                                                }
                                                if ($rules) {
                                                    $set('ingress_rules', $rules);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                        }
                                    } else {
                                        $set('connection_status', 'warning');
                                        $set('connection_message', 'Tunnel status: '.$status);
                                    }
                                } catch (\Exception $e) {
                                    $set('connection_status', 'error');
                                    $set('connection_message', $e->getMessage());
                                    $set('tunnel_status', 'down');
                                }
                            }),
                    ]),

                    Forms\Components\Hidden::make('connection_status'),
                    Forms\Components\Hidden::make('connection_message'),
                    Forms\Components\Hidden::make('tunnel_status')
                        ->rule(function ($set) {
                            return function (string $attribute, $value, \Closure $fail) use ($set) {
                                if ($value !== 'healthy') {
                                    // Side effect: Show the red alert
                                    $set('connection_status', 'error');
                                    $set('connection_message', 'You must verify the tunnel connection involved before proceeding.');

                                    // Fail validation to block the step
                                    $fail('Tunnel must be healthy.');
                                }
                            };
                        }),
                ])
                ->afterValidation(function ($state, $set, $get) {
                    // Redundant check, but safe - enforces health check for BOTH Create and Edit
                    if ($get('tunnel_status') !== 'healthy') {
                        $set('connection_status', 'error');
                        $set('connection_message', 'Tunnel must be connected and healthy before proceeding. Please check connection status.');
                        throw new Halt;
                    }
                }),

            // Step 3: Routing & DNS
            Components\Wizard\Step::make('Routing')
                ->description('Configure Routes')
                ->schema([
                    SimpleAlert::make('routing_info')
                        ->info()
                        ->title('Traffic Routing')
                        ->description('Map public hostnames to your local services. DNS records will be managed automatically.')
                        ->icon('heroicon-m-arrows-right-left'),

                    Forms\Components\Repeater::make('ingress_rules')
                        ->label('Ingress Rules')
                        ->itemLabel(fn (array $state): ?string => $state['hostname'] ?? null)
                        ->collapsed()
                        ->collapseAllAction(fn (Actions\Action $action) => $action->hidden())
                        ->expandAllAction(fn (Actions\Action $action) => $action->hidden())
                        ->schema([
                            Components\Grid::make(3)->schema([
                                Forms\Components\TextInput::make('hostname')
                                    ->label('Hostname')
                                    ->required()
                                    ->placeholder('sub.example.com')
                                    ->columnSpan(2),

                                Forms\Components\Select::make('cloudflare_domain_id')
                                    ->label('Zone')
                                    ->options(function ($livewire) {
                                        /** @var Cloudflare $account */
                                        $account = $livewire->ownerRecord;

                                        return $account->domains->pluck('name', 'id');
                                    })
                                    ->default(function ($livewire) {
                                        /** @var Cloudflare $account */
                                        $account = $livewire->ownerRecord;

                                        return $account->domains->count() === 1 ? $account->domains->first()->id : null;
                                    })
                                    ->searchable()
                                    ->required()
                                    ->columnSpan(1),
                            ]),

                            Forms\Components\TextInput::make('service')
                                ->label('Service')
                                ->required()
                                ->default('http://localhost:80')
                                ->placeholder('http://localhost:80'),

                            Components\Fieldset::make('Origin Request Settings')->schema([
                                Forms\Components\TextInput::make('httpHostHeader')
                                    ->label('HTTP Host Header'),
                                Forms\Components\Checkbox::make('noTLSVerify')
                                    ->label('No TLS Verify')
                                    ->inline(),
                            ])->statePath('origin_request'),
                        ])
                        ->default(function ($record) use ($isEdit) {
                            if ($isEdit && $record) {
                                // Pre-populate with existing ingress rules
                                return $record->ingressRules->map(fn ($rule) => [
                                    'hostname' => $rule->hostname,
                                    'service' => $rule->service,
                                    'cloudflare_domain_id' => $rule->hostname ?
                                        CloudflareDomain::where('cloudflare_id', $record->cloudflare_id)
                                            ->whereRaw("? LIKE CONCAT('%', name)", [$rule->hostname])
                                            ->first()?->id : null,
                                    'origin_request' => $rule->origin_request,
                                ])->toArray();
                            }

                            return [['hostname' => '', 'service' => 'http://localhost:80']];
                        })
                        ->collapsible()
                        ->cloneable()
                        ->reorderable(),
                ]),
        ];
    }
}
