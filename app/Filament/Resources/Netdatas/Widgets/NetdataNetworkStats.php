<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;

class NetdataNetworkStats extends BaseWidget
{
    public ?Netdata $record = null;

    protected ?string $pollingInterval = '2s';

    protected int|string|array $columnSpan = 1;

    protected int | array | null $columns = 1;

    protected $listeners = ['refresh-netdata-network' => '$refresh'];

    protected function getStats(): array
    {
        $record = $this->record;

        if (! $record) {
            return [];
        }

        // 1. Fetch available interfaces
        $interfaces = $this->fetchAvailableInterfaces($record);

        // Filter based on saved settings if any
        $savedSettings = $record->network_settings ?? [];
        if (! empty($savedSettings) && is_array($savedSettings)) {
            $interfaces = array_filter($interfaces, function ($iface) use ($savedSettings) {
                return in_array($iface['name'], $savedSettings);
            });
        }

        $stats = [];
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        foreach ($interfaces as $interface) {
            $name = $interface['name'];

            // 2. Fetch chart data for sparkline (last 20 points)
            try {
                $apiUrl = "{$url}/api/v1/data?chart=net.{$name}&points=20&format=json&after=-20";

                $response = Http::withHeaders([
                    'cf-access-client-id' => $record->access?->client_id,
                    'cf-access-client-secret' => $record->access?->client_secret,
                ])->timeout(2)->get($apiUrl); // Short timeout for loop

                if ($response->successful()) {
                    $data = $response->json();
                    $values = $data['data'] ?? []; // Newest first

                    // Latest values
                    $latestRecv = isset($values[0]) ? round($values[0][1], 2) : 0; // kbps
                    $latestSent = isset($values[0]) ? abs(round($values[0][2], 2)) : 0; // kbps

                    // Convert to Bytes/s for formatting
                    $recBytes = $latestRecv * 1000 / 8;
                    $sentBytes = $latestSent * 1000 / 8;

                    $recFormatted = Number::fileSize($recBytes, 1).'/s';
                    $sentFormatted = Number::fileSize($sentBytes, 1).'/s';

                    // Prepare sparkline data (sum of recv + sent for total activity visual)
                    // Reverse to be chronological
                    $chartData = [];
                    foreach (array_reverse($values) as $point) {
                        $recv = $point[1];
                        $sent = abs($point[2]);
                        $chartData[] = $recv + $sent;
                    }

                    $color = match (true) {
                        $latestRecv > 1000 || $latestSent > 1000 => 'success', // Activity > 1KB/s
                        default => 'gray', // Idle
                    };

                    $icon = match (true) {
                        str_starts_with($name, 'w') => 'heroicon-m-wifi', // wlp, wlx (Wi-Fi)
                        str_starts_with($name, 'vmbr') => 'heroicon-m-rectangle-stack', // vmbr (Bridge)
                        str_starts_with($name, 'veth') => 'heroicon-m-cube', // veth (Virtual/Container)
                        str_starts_with($name, 'docker') => 'heroicon-m-cube', // docker
                        default => 'heroicon-m-arrows-right-left', // eno, enp, eth (Ethernet/Physical)
                    };

                    $stats[] = Stat::make('Live Traffic', $name)
                        ->description("↓ {$recFormatted} / ↑ {$sentFormatted}")
                        ->icon($icon)
                        ->chart($chartData)
                        ->color($color);
                } else {
                    $stats[] = Stat::make($name, 'Offline')->color('danger');
                }

            } catch (\Exception $e) {
                $stats[] = Stat::make($name, 'Error')->color('danger');
            }
        }

        return $stats;
    }

    protected function fetchAvailableInterfaces(Netdata $record): array
    {
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            $apiUrl = "{$url}/api/v1/allmetrics?format=json&filter=net_speed.*";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $processedInterfaces = [];

                foreach ($data as $key => $interfaceData) {
                    $name = $interfaceData['family'] ?? $key;
                    // dimensions->speed->value is instantaneous, but we don't strictly need it inside the list fetch

                    $processedInterfaces[] = [
                        'name' => $name,
                    ];
                }

                return $processedInterfaces;
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
