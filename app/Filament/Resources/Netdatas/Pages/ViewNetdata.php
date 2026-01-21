<?php

namespace App\Filament\Resources\Netdatas\Pages;

use App\Filament\Resources\Netdatas\NetdataResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNetdata extends ViewRecord
{
    protected static string $resource = NetdataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            $this->selectResourcesAction(),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\Netdatas\Widgets\NetdataStatsOverview::class,
        ];
    }

    protected $listeners = ['refresh-netdata-layout' => '$refresh'];

    public function selectResourcesAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('select_resources')
            ->label('Select Resources')
            ->icon('mdi-view-dashboard-edit-outline')
            ->color('primary')
            ->fillForm(function () {
                $record = $this->getRecord();

                // Disks
                $savedDiskSettings = $record->disk_settings ?? [];
                $fetchedDisks = $this->fetchAvailableDisks($record);
                $allDiskNames = array_column($fetchedDisks, 'name');
                $selectedDisks = !empty($savedDiskSettings) ? $savedDiskSettings : $allDiskNames;

                // Network
                $savedNetworkSettings = $record->network_settings ?? [];
                $fetchedInterfaces = $this->fetchAvailableInterfaces($record);
                $allInterfaceNames = array_column($fetchedInterfaces, 'name');
                $selectedInterfaces = !empty($savedNetworkSettings) ? $savedNetworkSettings : $allInterfaceNames;

                // Widget Settings
                $widgetSettings = $record->widget_settings ?? [];

                return [
                    'selected_disks' => $selectedDisks,
                    'selected_interfaces' => $selectedInterfaces,
                ];
            })
            ->form(function () {
                return [
                    \Filament\Schemas\Components\Section::make('Disks')
                        ->schema([
                            \JaOcero\RadioDeck\Forms\Components\RadioDeck::make('selected_disks')
                                ->label('Select disks to display')
                                ->options(function () {
                                    $disks = $this->fetchAvailableDisks($this->getRecord());
                                    $options = [];
                                    foreach ($disks as $disk) {
                                        $options[$disk['name']] = $disk['name'];
                                    }

                                    return $options;
                                })
                                ->descriptions(function () {
                                    $disks = $this->fetchAvailableDisks($this->getRecord());
                                    $descriptions = [];
                                    foreach ($disks as $disk) {
                                        $descriptions[$disk['name']] = ($disk['total'] ?? 'N/A') . ' total';
                                    }

                                    return $descriptions;
                                })
                                ->icons(function () {
                                    $disks = $this->fetchAvailableDisks($this->getRecord());
                                    $icons = [];
                                    foreach ($disks as $disk) {
                                        $icons[$disk['name']] = 'mdi-harddisk';
                                    }

                                    return $icons;
                                })
                                ->multiple()
                                ->required()
                                ->columns(3)
                                ->color('primary'),
                        ]),

                    \Filament\Schemas\Components\Section::make('Network Interfaces')
                        ->schema([
                            \JaOcero\RadioDeck\Forms\Components\RadioDeck::make('selected_interfaces')
                                ->label('Select interfaces to display')
                                ->options(function () {
                                    $ifaces = $this->fetchAvailableInterfaces($this->getRecord());
                                    $options = [];
                                    foreach ($ifaces as $iface) {
                                        $options[$iface['name']] = $iface['name'];
                                    }

                                    return $options;
                                })
                                ->icons(function () {
                                    $ifaces = $this->fetchAvailableInterfaces($this->getRecord());
                                    $icons = [];
                                    foreach ($ifaces as $iface) {
                                        $icons[$iface['name']] = 'mdi-ethernet';
                                    }

                                    return $icons;
                                })
                                ->multiple()
                                ->required()
                                ->columns(3)
                                ->color('success'),
                        ]),
                ];
            })
            ->action(function (array $data) {
                $record = $this->getRecord();
                $record->update([
                    'disk_settings' => $data['selected_disks'] ?? [],
                    'network_settings' => $data['selected_interfaces'] ?? [],
                ]);

                \Filament\Notifications\Notification::make()
                    ->title('Resource selection saved')
                    ->success()
                    ->send();

                $this->dispatch('refresh-netdata-disks');
                $this->dispatch('refresh-netdata-network');
                $this->dispatch('refresh-netdata-layout'); // Trigger page refresh
            });
    }

    protected function fetchAvailableDisks(\App\Models\Netdata $record): array
    {
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            $apiUrl = "{$url}/api/v1/allmetrics?format=json&filter=disk_space.*&help=no&types=no&timings=no";

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $processedDisks = [];

                foreach ($data as $key => $diskData) {
                    $dims = $diskData['dimensions'] ?? [];
                    $avail = $dims['avail']['value'] ?? 0;
                    $used = $dims['used']['value'] ?? 0;
                    $reserved = $dims['reserved_for_root']['value'] ?? 0;
                    $total = $avail + $used + $reserved;

                    $multiplier = 1024 * 1024 * 1024; // GiB assumed

                    $processedDisks[] = [
                        'name' => $diskData['family'] ?? $key,
                        'total' => \Illuminate\Support\Number::fileSize($total * $multiplier, 2),
                    ];
                }

                return $processedDisks;
            }
        } catch (\Exception $e) {
            //
        }

        return [];
    }

    protected function fetchAvailableInterfaces(\App\Models\Netdata $record): array
    {
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            $apiUrl = "{$url}/api/v1/allmetrics?format=json&filter=net_speed.*";

            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $processedInterfaces = [];

                foreach ($data as $key => $interfaceData) {
                    $name = $interfaceData['family'] ?? $key;
                    $processedInterfaces[] = [
                        'name' => $name,
                    ];
                }

                return $processedInterfaces;
            }
        } catch (\Exception $e) {
            //
        }

        return [];
    }
}
