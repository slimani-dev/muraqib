<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NetdataNetwork extends ApexChartWidget
{
    protected $listeners = ['refresh-netdata-network' => 'updateChart'];

    private ?array $cachedData = null;

    public function updateChart(): void
    {
        $this->cachedData = null;
        $this->updateOptions();
    }

    public function getDescription(): ?string
    {
        $data = $this->getChartData();

        if (empty($data)) {
            return null;
        }

        // Netdata is usually in Kilobits/s for net charts, unless configured otherwise.
        // Assuming Kilobits per second (kb/s).
        // 1 kb/s = 1000 bits/s = 125 bytes/s.
        $rec = $data['latest']['received'] ?? 0;
        $sent = $data['latest']['sent'] ?? 0;

        // Convert kilobits to bytes for Number::fileSize
        $recBytes = $rec * 1000 / 8;
        $sentBytes = $sent * 1000 / 8;

        $recFormatted = Number::fileSize($recBytes, 1).'/s';
        $sentFormatted = Number::fileSize($sentBytes, 1).'/s';

        return "↓ {$recFormatted} / ↑ {$sentFormatted}";
    }

    /**
     * Fetch and cache chart data to avoid duplicate API calls for description and chart options
     */
    protected function getChartData(): array
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

        $record = $this->record;

        // Get interface from saved settings (use the first one)
        $savedSettings = $record->network_settings ?? [];
        $interfaceName = $savedSettings[0] ?? null;

        if (! $record || ! $interfaceName) {
            $this->cachedData = [];

            return [];
        }

        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            $apiUrl = "{$url}/api/v1/data?chart=net.{$interfaceName}&points=60&format=json&after=-60";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(5)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                $values = $data['data'] ?? [];

                $times = [];
                $received = [];
                $sent = [];

                // Netdata returns newest first, reverse to show chronological
                $valuesChronological = array_reverse($values);

                foreach ($valuesChronological as $point) {
                    $times[] = date('H:i:s', $point[0]);
                    $received[] = round($point[1], 2);
                    $sent[] = abs(round($point[2], 2));
                }

                // Latest values (from the ORIGINAL 'values' array which is newest first)
                $latestRecv = isset($values[0]) ? round($values[0][1], 2) : 0;
                $latestSent = isset($values[0]) ? abs(round($values[0][2], 2)) : 0;

                $this->cachedData = [
                    'times' => $times,
                    'received' => $received,
                    'sent' => $sent,
                    'interfaceName' => $interfaceName,
                    'latest' => [
                        'received' => $latestRecv,
                        'sent' => $latestSent,
                    ],
                ];

                return $this->cachedData;
            }
        } catch (\Exception $e) {
            //
        }

        $this->cachedData = [];

        return [];
    }

    protected function getOptions(): array
    {
        $data = $this->getChartData();

        if (empty($data)) {
            return [];
        }

        return [
            'chart' => [
                'type' => 'area',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
                'sparkline' => [
                    'enabled' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Received',
                    'data' => $data['received'],
                ],
                [
                    'name' => 'Sent',
                    'data' => $data['sent'],
                ],
            ],
            'xaxis' => [
                'categories' => $data['times'],
                'labels' => [
                    'show' => false,
                ],
                'axisBorder' => [
                    'show' => false,
                ],
                'axisTicks' => [
                    'show' => false,
                ],
                'tooltip' => [
                    'enabled' => false,
                ],
            ],
            'yaxis' => [
                'show' => false,
            ],
            'grid' => [
                'show' => false,
            ],
            'legend' => [
                'show' => false,
            ],
            'colors' => ['#10b981', '#3b82f6'],
            'stroke' => [
                'curve' => 'smooth',
                'width' => 2,
            ],
            'dataLabels' => [
                'enabled' => false,
            ],
            'title' => [
                'text' => $data['interfaceName'],
                'align' => 'left',
                'style' => [
                    'fontFamily' => 'inherit',
                    'fontWeight' => 600,
                ],
            ],
        ];
    }
}
