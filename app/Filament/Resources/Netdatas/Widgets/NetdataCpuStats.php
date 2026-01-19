<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;

class NetdataCpuStats extends BaseWidget
{
    public ?Netdata $record = null;

    protected ?string $pollingInterval = '2s';

    protected int|string|array $columnSpan = 1;

    public ?string $cpu = null;
    public ?string $cores = null;

    protected function getStats(): array
    {
        $record = $this->record;

        if (!$record) {
            return [];
        }

        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            // 1. Fetch CPU Info (One-time, persisted in Livewire component)
            if ($this->cpu === null) {
                try {
                    $infoResponse = Http::withHeaders([
                        'cf-access-client-id' => $record->access?->client_id,
                        'cf-access-client-secret' => $record->access?->client_secret,
                    ])->timeout(2)->get("{$url}/api/v1/info");

                    if ($infoResponse->successful()) {
                        $infoData = $infoResponse->json();
                        $model = $infoData['host_labels']['_system_cpu_model'] ?? 'CPU';
                        $cores = $infoData['host_labels']['_system_cores'] ?? '?';
                        $this->cpu = "{$model} ";
                        $this->cores = "({$cores} Cores)";
                    } else {
                        $this->cpu = ''; // Do not retry repeatedly on failure to avoid spamming
                        $this->cores = '';
                    }
                } catch (\Exception $e) {
                    $this->cpu = '';
                    $this->cores = '';
                }
            }

            // 2. Fetch CPU Usage Data
            $apiUrl = "{$url}/api/v1/data?chart=system.cpu&points=20&format=json&after=-20&options=unaligned";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(2)->get($apiUrl);

            if (!$response->successful()) {
                return [Stat::make('CPU Usage', 'Offline')->color('danger')];
            }

            $data = $response->json();
            $labels = $data['labels'] ?? []; // ["time", "guest_nice", "guest", "steal", "softirq", "irq", "user", "system", "nice", "iowait"]
            $values = $data['data'] ?? []; // Newest first

            // Indices
            $idxUser = array_search('user', $labels);
            $idxSystem = array_search('system', $labels);
            $idxIowait = array_search('iowait', $labels);

            // Earliest (index 0 for reverse or just iter)
            // Values are newest first.
            $latestPoint = $values[0] ?? [];
            $latestUser = ($idxUser !== false) ? ($latestPoint[$idxUser] ?? 0) : 0;
            $latestSystem = ($idxSystem !== false) ? ($latestPoint[$idxSystem] ?? 0) : 0;
            $latestIowait = ($idxIowait !== false) ? ($latestPoint[$idxIowait] ?? 0) : 0;

            $totalUsage = $latestUser + $latestSystem + $latestIowait;

            // Chart Data
            $chartData = [];
            foreach (array_reverse($values) as $point) {
                $u = ($idxUser !== false) ? ($point[$idxUser] ?? 0) : 0;
                $s = ($idxSystem !== false) ? ($point[$idxSystem] ?? 0) : 0;
                $i = ($idxIowait !== false) ? ($point[$idxIowait] ?? 0) : 0;
                $chartData[] = $u + $s + $i;
            }

            $statusText = match (true) {
                $totalUsage > 80 => 'Heavy Load',
                $totalUsage > 50 => 'Moderate Load',
                default => 'Stable',
            };

            $color = match (true) {
                $totalUsage > 80 => 'danger',
                $totalUsage > 50 => 'warning',
                default => 'success',
            };

            return [
                Stat::make("CPU {$this->cores}", $this->cleanCpuName($this->cpu))
                    ->description(number_format($totalUsage, 1) . '%')
                    ->icon('heroicon-m-cpu-chip')
                    ->chart($chartData)
                    ->color($color),
            ];

        } catch (\Exception $e) {
            return [Stat::make('CPU Usage', 'Error')->color('danger')];
        }
    }

    public function cleanCpuName(string $fullName): string
    {
        $replacements = [
            '/\(R\)/i'         => '',
            '/\(TM\)/i'        => '',
            '/Core /i'         => '',
            '/\d+th Gen /i'    => '', // Removes 11th Gen, 12th Gen, etc.
            '/\d+rd Gen /i'    => '',
            '/  +/'            => ' ', // Remove double spaces
        ];

        $cleanName = preg_replace(array_keys($replacements), array_values($replacements), $fullName);

        return trim($cleanName);
    }
}
