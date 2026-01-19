<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\IconPosition;
use Filament\Widgets\Widget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class NetdataSystemInfo extends Widget implements HasActions, HasForms, HasInfolists
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithInfolists;
    use InteractsWithSchemas {
        InteractsWithSchemas::getCachedSchemas as getCachedSchemasFromSchemas;
        InteractsWithForms::getCachedSchemas insteadof InteractsWithSchemas;
    }

    protected string $view = 'filament.resources.netdatas.widgets.netdata-system-info';

    protected int|string|array $columnSpan = 2;

    public ?Netdata $record = null;

    // Placeholder Properties
    public string $hostname = '-';

    public string $ip = '-';

    public string $nodeId = '-';

    public string $status = 'Live';

    public string $agentVersion = '-';

    public string $systemType = '-';

    public string $os = '_';

    public string $architecture = '-';

    public string $kernel = '-';

    public string $cpu = '-';

    public string $cpu_cors = '-';

    public string $memory = '-';

    public string $diskSize = '-';

    public string $timezone = '-';

    /**
     * @throws ConnectionException
     */
    public function mount(): void
    {
        $this->fetchData();
    }

    public function fetchData(): void
    {
        $record = $this->record;
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get("{$url}/api/v1/info");

            if ($response->successful()) {
                $data = $response->json();

                $this->hostname = $data['host_labels']['_hostname'] ?? '-';
                $this->ip = $data['host_labels']['_net_default_iface_ip'] ?? '-';

                $this->os = ($data['os_name'] ?? '').' '.($data['os_version'] ?? '');
                $this->architecture = $data['architecture'] ?? '-';
                $this->kernel = ($data['kernel_name'] ?? '').', '.($data['kernel_version'] ?? '');
                $this->timezone = $data['host_labels']['_timezone'] ?? '-';

                $this->cpu = $data['host_labels']['_system_cpu_model'] ?? '-';
                $this->cpu_cors = $data['host_labels']['_system_cores'] ?? '-';

                $this->memory = isset($data['host_labels']['_system_ram_total'])
                    ? Number::fileSize($data['host_labels']['_system_ram_total'], 1).' RAM'
                    : '-';
                $this->diskSize = isset($data['host_labels']['_system_disk_space'])
                    ? Number::fileSize($data['host_labels']['_system_disk_space'], 2)
                    : '-';

                $this->agentVersion = $data['version'] ?? '-';
                $this->nodeId = $data['uid'] ?? '-';
                $this->status = 'Live';
            } else {
                $this->status = 'Error: '.$response->status();
            }
        } catch (\Exception $e) {
            $this->status = 'Connection Error';
        }
    }

    public function infolist(Schema $schema): Schema
    {
        // ... (data definitions) ...
        $info = [
            'OS' => $this->os,
            'Kernal' => $this->kernel,
            'Arch' => $this->architecture,
            'Timezone' => $this->timezone,
        ];

        $specs = [
            [
                'icon' => 'mdi-cpu-64-bit',
                'label' => 'CPU',
                'value' => $this->cpu,
                'extra' => "{$this->cpu_cors} Cors",
            ], [
                'icon' => 'mdi-memory',
                'label' => 'Memory',
                'value' => $this->memory,
                'extra' => '',
            ], [
                'icon' => 'mdi-harddisk',
                'label' => 'Disk',
                'value' => $this->diskSize,
                'extra' => '',
            ], [
                'icon' => 'si-netdata',
                'label' => 'Netdata',
                'value' => $this->agentVersion,
                'extra' => '',
            ],
        ];

        return $schema
            ->schema([
                Section::make($this->hostname)
                    ->icon('mdi-information-outline')
                    ->iconColor(Color::Indigo)
                    ->description($this->ip)
                    ->headerActions([
                        Action::make($this->status)
                            ->outlined()
                            ->icon('mdi-check-circle-outline')
                            ->color(fn () => $this->status == 'Live' ? 'success' : 'danger')
                            ->button()
                            ->tooltip('Click to refresh')
                            ->action(function () {
                                $this->fetchData();
                                \Filament\Notifications\Notification::make()
                                    ->title('System info refreshed')
                                    ->success()
                                    ->send();
                            }),
                    ])
                    ->footer([
                        TextEntry::make('Node Id')
                            ->inlineLabel()
                            ->state($this->nodeId)
                            ->afterLabel($this->agentVersion)
                            ->copyable()
                            ->icon('mdi-content-copy')
                            ->iconPosition(IconPosition::After)
                            ->extraAttributes(['class' => 'w-full flex items-center justify-between']),
                    ])
                    ->schema([
                        KeyValueEntry::make('info')
                            ->hiddenLabel()
                            ->state($info)
                            ->extraAttributes([
                                'class' => 'no-header',
                            ]),

                        RepeatableEntry::make('specs')
                            ->state($specs)
                            ->hiddenLabel()
                            ->grid(2)
                            ->schema(fn (array $state) => [
                                Group::make(function (Group $component) use ($state) {
                                    $index = str($component->getStatePath())->remove('specs.')->toInteger();

                                    return [
                                        TextEntry::make('label')
                                            ->label(fn () => $state[$index]['label'])
                                            ->icon(fn () => $state[$index]['icon'])
                                            ->iconColor(Color::Indigo)
                                            ->helperText(fn () => $state[$index]['value'])
                                            ->hiddenLabel()
                                            ->afterContent(fn () => new HtmlString("<span class='text-nowrap whitespace-nowrap text-gray-400'> {$state[$index]['extra']} </span>")),
                                    ];
                                }),
                            ]),
                    ]),
            ]);
    }
}
