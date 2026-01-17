<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use App\Models\Cloudflare;
use App\Models\CloudflareTunnel;
use App\Services\Cloudflare\CloudflareService;
use CodeWithDennis\SimpleAlert\Components\SimpleAlert;
use Filament\Actions;
use Filament\Forms;
use Filament\Schemas;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Exceptions\Halt;
use Illuminate\Support\Facades\Cache;
use LaraZeus\TorchFilament\Infolists\TorchEntry;
use Webbingbrasil\FilamentCopyActions\Actions\CopyAction;

class WizardCloudflareForm
{
    public static function getSteps(): array
    {
        return [
            Components\Wizard\Step::make('Account')
                ->description('Connect Cloudflare')
                ->schema([
                    SimpleAlert::make('guidelines')
                        ->info()
                        ->title('First time?')
                        ->description('We need your Cloudflare credentials to create a secure tunnel.')
                        ->icon('heroicon-m-information-circle'),

                    Forms\Components\TextInput::make('name')
                        ->label('Account Name')
                        ->required()
                        ->default('My Cloudflare'),

                    Forms\Components\TextInput::make('account_id')
                        ->label('Cloudflare Account ID')
                        ->required()
                        ->placeholder('e.g. from URL: dash.cloudflare.com/YOUR_ACCOUNT_ID')
                        ->helperText(new \Illuminate\Support\HtmlString('
                            <div class="space-y-2">
                                <p>You can extract this from your Cloudflare dashboard URL.</p>
                                <img src="https://i.postimg.cc/YSVMrCJG/image.png" alt="Cloudflare Account ID Location" class="rounded-lg border border-gray-200 dark:border-gray-700 shadow-sm max-w-sm" />
                            </div>
                        '))
                        ->hintAction(
                            Actions\Action::make('openDashboard')
                                ->label('Open Dashboard')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->url('https://dash.cloudflare.com/?to=/:account/api-tokens', shouldOpenInNewTab: true)
                        ),

                    Forms\Components\TextInput::make('api_token')
                        ->label('API Token')
                        ->password()
                        ->revealable()
                        ->required()
                        ->hintAction(
                            Actions\Action::make('createToken')
                                ->label('Create Token')
                                ->icon('heroicon-m-arrow-top-right-on-square')
                                ->url('https://dash.cloudflare.com/profile/api-tokens', shouldOpenInNewTab: true)
                        ),

                    Components\Image::make('https://i.postimg.cc/HLvFwv2F/image.png', '')
                        ->imageWidth('full')
                        ->imageHeight('auto'),

                    SimpleAlert::make('verify_error_alert')
                        ->danger()
                        ->title('Connection Failed')
                        ->description(fn($get) => $get('verify_message'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn($get) => $get('verify_status') === 'error'),

                    Forms\Components\Hidden::make('verify_status'),
                    Forms\Components\Hidden::make('verify_message'),
                ])
                ->afterValidation(function ($state, $set) {
                    // Verify Token
                    $service = app(CloudflareService::class);
                    try {
                        $valid = $service->verifyToken($state['api_token'], $state['account_id'] ?? null);
                        if (!$valid) {
                            throw new \Exception('Invalid API Token.');
                        }

                        $mockAccount = new Cloudflare(['api_token' => $state['api_token'], 'account_id' => $state['account_id']]);

                        // Cache Tunnels
                        try {
                            $tunnels = $service->listTunnels($mockAccount);
                            $tunnelsCollection = collect($tunnels);
                            Cache::put('wizard_tunnels_' . md5($state['api_token']), $tunnelsCollection, 300);

                            // Auto-select mode
                            $set('tunnel_mode', $tunnelsCollection->isEmpty() ? 'create' : null);
                        } catch (\Exception $e) {
                        }

                        // Cache Zones
                        try {
                            $zones = $service->listZones($state['api_token']);
                            $zonesCollection = collect($zones);
                            Cache::put('wizard_zones_' . md5($state['api_token']), $zonesCollection, 300);

                            if ($zonesCollection->count() === 1) {
                                $set('zone_id', $zonesCollection->first()['id']);
                            }
                        } catch (\Exception $e) {
                        }

                        $set('verify_status', 'success');
                        $set('verify_message', 'Connected successfully to Cloudflare API');
                    } catch (\Exception $e) {
                        $set('verify_status', 'error');
                        $set('verify_message', $e->getMessage());

                        // Halt wizard (throw to prevent 'Next')
                        throw new Halt;
                    }
                }),

            Components\Wizard\Step::make('Tunnel')
                ->description('Select or Create Tunnel')
                ->schema([
                    SimpleAlert::make('tunnel_guidelines')
                        ->info()
                        ->title('Secure Connection')
                        ->description('A tunnel creates a private, encrypted connection between your infrastructure and Cloudflare.')
                        ->icon('heroicon-m-shield-check'),

                    Forms\Components\Radio::make('tunnel_mode')
                        ->label('Tunnel Action')
                        ->options(function ($get) {
                            $token = $get('api_token');
                            $options = ['create' => 'Create New Tunnel'];

                            if ($token) {
                                $cacheKey = 'wizard_tunnels_' . md5($token);
                                if (Cache::has($cacheKey)) {
                                    $tunnels = Cache::get($cacheKey);
                                    if ($tunnels && $tunnels->isNotEmpty()) {
                                        $options['existing'] = 'Use Existing Tunnel';
                                    }
                                }
                            }

                            return $options;
                        })
                        ->live()
                        ->afterStateUpdated(fn($state, $set) => $state === 'create' ? $set('ingress_rules', [['hostname' => '', 'service' => 'http://localhost:80']]) : null),

                    Forms\Components\TextInput::make('new_tunnel_name')
                        ->default('muraqib-node')
                        ->helperText('A descriptive name for your tunnel. Use alphanumeric characters and hyphens.')
                        ->visible(fn($get) => $get('tunnel_mode') === 'create')
                        ->required(fn($get) => $get('tunnel_mode') === 'create'),

                    Forms\Components\Select::make('existing_tunnel_id')
                        ->visible(fn($get) => $get('tunnel_mode') === 'existing')
                        ->required(fn($get) => $get('tunnel_mode') === 'existing')
                        ->helperText('Selecting an existing tunnel will pre-populate its current configuration.')
                        ->options(function ($get) {
                            $token = $get('api_token');
                            if (!$token) {
                                return [];
                            }
                            $key = 'wizard_tunnels_' . md5($token);
                            if (Cache::has($key)) {
                                return Cache::get($key)->pluck('name', 'id');
                            }

                            return [];
                        })
                        ->live()
                        ->afterStateUpdated(function ($state, $set, $get) {
                            if (!$state) {
                                return;
                            }
                            $token = $get('api_token');
                            $accountId = $get('account_id');
                            if (!$token) {
                                return;
                            }

                            $service = app(CloudflareService::class);
                            $mockTunnel = new CloudflareTunnel(['tunnel_id' => $state]);
                            $mockTunnel->setRelation('cloudflare', new Cloudflare(['api_token' => $token, 'account_id' => $accountId]));

                            try {
                                $ingress = $service->getTunnelConfig($mockTunnel);
                                if (is_array($ingress)) {
                                    $rules = [];
                                    foreach ($ingress as $rule) {
                                        if (!empty($rule['hostname'])) {
                                            $item = ['hostname' => $rule['hostname'], 'service' => $rule['service'] ?? '', 'path' => $rule['path'] ?? null];
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
                        }),

                    SimpleAlert::make('fetch_error_alert')
                        ->danger()
                        ->title('Token Fetch Failed')
                        ->description(fn($get) => $get('fetch_message'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn($get) => $get('fetch_status') === 'error'),

                    Forms\Components\Hidden::make('fetch_status'),
                    Forms\Components\Hidden::make('fetch_message'),
                    Forms\Components\Hidden::make('tunnel_token')->live(),
                ])
                ->afterValidation(function ($state, $set, $get) {
                    $token = $get('api_token');
                    $accountId = $get('account_id');
                    $mode = $state['tunnel_mode'];

                    if (!$token || !$accountId) {
                        $set('fetch_status', 'error');
                        $set('fetch_message', 'API token and Account ID are required');

                        throw new \Exception('Credentials missing');
                    }

                    $service = app(CloudflareService::class);
                    try {
                        $account = new Cloudflare(['api_token' => $token, 'account_id' => $accountId]);

                        if ($mode === 'create') {
                            $name = $state['new_tunnel_name'] ?: 'muraqib-node';
                            $tunnelData = $service->findOrCreateTunnel($account, $name);
                            $tunnelId = $tunnelData['id'];
                        } else {
                            $tunnelId = $state['existing_tunnel_id'];
                        }

                        if (!$tunnelId) {
                            throw new \Exception('Please select or name a tunnel first.');
                        }

                        $mockModel = new \App\Models\CloudflareTunnel(['tunnel_id' => $tunnelId]);
                        $mockModel->setRelation('cloudflare', $account);

                        $tunnelToken = $service->getTunnelToken($mockModel);
                        $set('tunnel_token', $tunnelToken);
                        $set('current_tunnel_id', $tunnelId);

                        $set('fetch_status', 'success');
                        $set('fetch_message', 'Tunnel token retrieved successfully');
                    } catch (\Exception $e) {
                        $set('fetch_status', 'error');
                        $set('fetch_message', $e->getMessage());

                        throw new Halt;
                    }
                }),

            Components\Wizard\Step::make('Installation')
                ->description('Install Cloudflare Agent')
                ->visible(fn($get) => filled($get('tunnel_token')))
                ->schema([
                    SimpleAlert::make('install_guidelines')
                        ->info()
                        ->title('Deployment')
                        ->description('Run one of the commands below on your server to connect it to this tunnel.')
                        ->icon('heroicon-m-rocket-launch'),

                    Components\Tabs::make('Installation Method')
                        ->tabs(function (Components\Utilities\Get $get) {
                            $tunnelToken = $get('tunnel_token');
                            $accountId = $get('account_id');
                            $currentTunnelId = $get('current_tunnel_id');

                            $yaml = <<<'YAML'
services:
    cloudflared:
        image: cloudflare/cloudflared:latest
        command: tunnel run
        environment:
        - TUNNEL_TOKEN=${TUNNEL_TOKEN}
        restart: always
YAML;

                            $dotEnv = <<<ENV
TUNNEL_TOKEN={$tunnelToken}
ENV;

                            $linuxCommand1 = <<<'BASH'
 # Add cloudflare gpg key
 sudo mkdir -p --mode=0755 /usr/share/keyrings
 curl -fsSL https://pkg.cloudflare.com/cloudflare-public-v2.gpg | sudo tee /usr/share/keyrings/cloudflare-public-v2.gpg >/dev/null

 # Add this repo to your apt repositories
 echo 'deb [signed-by=/usr/share/keyrings/cloudflare-public-v2.gpg] https://pkg.cloudflare.com/cloudflared any main' | sudo tee /etc/apt/sources.list.d/cloudflared.list

 # install cloudflared
 sudo apt-get update && sudo apt-get install cloudflared
BASH;

                            $linuxCommand2 = <<<BASH
 sudo cloudflared service install "{$tunnelToken}"
BASH;

                            $dockerRunCommand = <<<BASH
 docker run cloudflare/cloudflared:latest tunnel \
    --no-autoupdate run \
    --token {$tunnelToken}
BASH;

                            return [
                                Components\Tabs\Tab::make('Portainer / Docker Compose')
                                    ->icon('si-portainer')
                                    ->schema([
                                        TextEntry::make('docker_guide')
                                            ->hiddenLabel()
                                            ->state('If you use Portainer or Docker Compose, use this configuration:'),

                                        TorchEntry::make('code')
                                            ->columnSpanFull()
                                            ->withWrapper()
                                            ->grammar('yaml')
                                            ->hintActions([
                                                CopyAction::make()->copyable($yaml),
                                            ])
                                            ->state($yaml),

                                        TorchEntry::make('.env')
                                            ->columnSpanFull()
                                            ->grammar('yaml')
                                            ->hintActions([
                                                CopyAction::make()->copyable($dotEnv),
                                            ])
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
                                            ->hintActions([
                                                CopyAction::make()->copyable("docker run -d --restart always --name cloudflared -e TUNNEL_TOKEN={$tunnelToken} cloudflare/cloudflared:latest tunnel run"),
                                            ])
                                            ->state($dockerRunCommand),
                                    ]),

                                Components\Tabs\Tab::make('Ubuntu / Debian (CLI)')
                                    ->icon('mdi-console')
                                    ->schema([
                                        TextEntry::make('docker_guide')
                                            ->hiddenLabel()
                                            ->state('Run these commands on your Linux server:')
                                            ->helperText('.deb - amd64 / x86-64'),

                                        TorchEntry::make('command')
                                            ->columnSpanFull()
                                            ->grammar('shell')
                                            ->hintActions([
                                                CopyAction::make()->copyable($linuxCommand1),
                                            ])
                                            ->state($linuxCommand1),

                                        TorchEntry::make('command2')
                                            ->columnSpanFull()
                                            ->grammar('shell')
                                            ->hintActions([
                                                CopyAction::make()->copyable($linuxCommand2),
                                            ])
                                            ->state($linuxCommand2),
                                    ]),

                                Components\Tabs\Tab::make('More Download Options')
                                    ->icon('mdi-cloud-download')
                                    ->schema([
                                        TextEntry::make('docker_guide')
                                            ->hiddenLabel()
                                            ->state('Cloudflared is available for Windows, macOS, and various Linux distributions in multiple package formats (.rpm, .deb, etc.).')
                                            ->helperText('Head to Cloudflare dashboard -> Access -> Tunnels to create your first Tunnel. There, you will get a single line command to start and run your cloudflared docker container authenticating to your Cloudflare account.'),

                                        Actions\Action::make('Direct link to dashboard')
                                            ->icon('mdi-open-in-new')
                                            ->iconPosition(IconPosition::After)
                                            ->link()
                                            ->url("https://one.dash.cloudflare.com/$accountId/networks/connectors/cloudflare-tunnels/cfd_tunnel/$currentTunnelId/edit?tab=overview")
                                            ->openUrlInNewTab(),
                                    ]),
                            ];
                        }),

                    SimpleAlert::make('tunnel_status_alert')
                        ->danger()
                        ->title('Tunnel Disconnected')
                        ->description('Your tunnel is not yet connected to Cloudflare. Please run the installer above.')
                        ->icon('heroicon-m-exclamation-triangle')
                        ->visible(fn($get) => filled($get('tunnel_status')) && $get('tunnel_status') === 'down'),

                    SimpleAlert::make('connection_alert')
                        ->success()
                        ->title('Connection Status')
                        ->description(fn($get) => $get('connection_message'))
                        ->icon('heroicon-m-check-circle')
                        ->visible(fn($get) => $get('connection_status') === 'success'),

                    SimpleAlert::make('connection_warning_alert')
                        ->warning()
                        ->title('Connection Warning')
                        ->description(fn($get) => $get('connection_message'))
                        ->icon('heroicon-m-exclamation-triangle')
                        ->visible(fn($get) => $get('connection_status') === 'warning'),

                    SimpleAlert::make('connection_error_alert')
                        ->danger()
                        ->title('Connection Error')
                        ->description(fn($get) => $get('connection_message'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn($get) => $get('connection_status') === 'error'),

                    Components\Actions::make([
                        Actions\Action::make('checkConnection')
                            ->label('Check Connection')
                            ->icon('heroicon-m-arrow-path')
                            ->color('warning')
                            ->action(function ($get, $set) {
                                $currentTunnelId = $get('current_tunnel_id');
                                if (!$currentTunnelId) {
                                    $set('connection_status', 'error');
                                    $set('connection_message', 'No tunnel selected. Please fetch tunnel token first.');
                                    $set('tunnel_status', 'down');

                                    return;
                                }

                                $service = app(CloudflareService::class);
                                $token = $get('api_token');
                                $accountId = $get('account_id');
                                $account = new \App\Models\Cloudflare(['api_token' => $token, 'account_id' => $accountId]);

                                $tunnel = new \App\Models\CloudflareTunnel(['tunnel_id' => $currentTunnelId]);
                                $tunnel->setRelation('cloudflare', $account);

                                try {
                                    $details = $service->getTunnelDetails($tunnel);
                                    $status = $details['status'] ?? 'down'; // healthy, down, inactive
                                    $set('tunnel_status', $status);

                                    if ($status === 'healthy') {
                                        $set('connection_status', 'success');
                                        $set('connection_message', 'Tunnel is connected and healthy');
                                    } else {
                                        $set('connection_status', 'warning');
                                        $set('connection_message', 'Tunnel status: ' . $status);
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
                    Forms\Components\Hidden::make('tunnel_status'),
                    Forms\Components\Hidden::make('current_tunnel_id'),
                ])
                ->afterValidation(function ($state, $set, $get) {
                    $tunnelStatus = $get('tunnel_status');

                    // Require a healthy tunnel to proceed
                    if ($tunnelStatus !== 'healthy') {
                        $set('connection_status', 'error');
                        $set('connection_message', 'Tunnel must be connected and healthy before proceeding. Please check connection status.');

                        throw new Halt;
                    }
                }),

            Components\Wizard\Step::make('Routing')
                ->description('Configure Ingress & DNS')
                ->schema([
                    SimpleAlert::make('routing_guidelines')
                        ->info()
                        ->title('Traffic Routing')
                        ->description('Map public hostnames to your local services. CNAME records will be managed automatically.')
                        ->icon('heroicon-m-arrows-right-left'),

                    Forms\Components\Select::make('zone_id')
                        ->options(function ($get) {
                            $token = $get('api_token');
                            if (!$token) {
                                return [];
                            }
                            $key = 'wizard_zones_' . md5($token);
                            if (Cache::has($key)) {
                                return Cache::get($key)->pluck('name', 'id');
                            }

                            return [];
                        })
                        ->searchable()
                        ->required()
                        ->live(),

                    Forms\Components\Repeater::make('ingress_rules')
                        ->itemLabel(fn(array $state): ?string => $state['hostname'] ?? null)
                        ->collapsed()
                        ->collapseAllAction(fn(Actions\Action $action) => $action->label('Collapse all members')->hidden())
                        ->expandAllAction(fn(Actions\Action $action) => $action->label('Collapse all members')->hidden())
                        ->schema([
                            Components\Grid::make(2)->schema([
                                Forms\Components\TextInput::make('hostname')->required(),
                                Forms\Components\TextInput::make('service')->required(),
                            ]),

                            Components\Fieldset::make('Settings')->schema([
                                Forms\Components\TextInput::make('httpHostHeader'),
                                Forms\Components\Checkbox::make('noTLSVerify')
                                    ->label('No TLS Verify')
                                    ->inline(),
                            ])->statePath('origin_request'),
                        ])
                        ->default([['hostname' => '', 'service' => 'http://localhost:80']])
                        ->collapsible()
                        ->cloneable(),

                    SimpleAlert::make('deploy_alert')
                        ->success()
                        ->title('Deployment Successful')
                        ->description(fn($get) => $get('deploy_message'))
                        ->icon('heroicon-m-check-circle')
                        ->visible(fn($get) => $get('deploy_status') === 'success'),

                    SimpleAlert::make('deploy_error_alert')
                        ->danger()
                        ->title('Deployment Failed')
                        ->description(fn($get) => $get('deploy_message'))
                        ->icon('heroicon-m-x-circle')
                        ->visible(fn($get) => $get('deploy_status') === 'error'),

                    Forms\Components\Hidden::make('deploy_status'),
                    Forms\Components\Hidden::make('deploy_message'),
                ]),
        ];
    }
}
