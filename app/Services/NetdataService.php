<?php

namespace App\Services;

use App\Models\Netdata;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;

class NetdataService
{
    /**
     * Fetch all stats for a given Netdata record.
     */
    public function getStats(Netdata $record, string $timeframe = '1h'): ?array
    {
        $url = $this->getBaseUrl($record);
        $headers = $this->getAuthHeaders($record);

        $filter = 'system.cpu system.ram disk_space.* net_speed.* *_rtt';
        $allMetricsUrl = "{$url}/api/v1/allmetrics?format=json&help=no&types=no&timings=no&filter=".urlencode($filter);

        try {
            $response = Http::withHeaders($headers)->connectTimeout(3)->timeout(5)->get($allMetricsUrl);

            if (! $response->successful()) {
                return null;
            }

            $allData = $response->json();

            $disksToProcess = $this->getFilteredDisks($record, $allData);
            $networksToProcess = $this->getFilteredNetworks($record, $allData);

            // A failed pool request comes back as an exception; treat it as missing data.
            $historyResponses = array_map(
                fn ($response) => $response instanceof Response ? $response : null,
                Http::pool(fn (Pool $pool) => $this->preparePoolRequests($pool, $url, $headers, $networksToProcess, $timeframe)),
            );

            return [
                'status' => 'online',
                'info' => $this->buildSystemInfo($historyResponses['info'] ?? null),
                'cpu' => $this->buildCpuData($allData['system.cpu'] ?? [], $historyResponses['cpu'] ?? null, $historyResponses['info'] ?? null),
                'memory' => $this->buildMemoryData($allData['system.ram'] ?? [], $historyResponses['ram'] ?? null),
                'networks' => $this->buildNetworkData($networksToProcess, $historyResponses),
                'disks' => $this->buildDiskData($disksToProcess),
                'ping' => $this->buildPingData($allData),
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    protected function preparePoolRequests(Pool $pool, string $url, array $headers, array $networks, string $timeframe): array
    {
        $requests = [];

        $seconds = match ($timeframe) {
            '6h' => 6 * 3600,
            '12h' => 12 * 3600,
            '24h' => 24 * 3600,
            '3d' => 3 * 24 * 3600,
            '7d' => 7 * 24 * 3600,
            '1m' => 30 * 24 * 3600,
            default => 3600, // 1h
        };

        $points = 60; // Pull more points for the wider charts
        $commonParams = "points={$points}&format=json&after=-{$seconds}&options=unaligned";

        // Fail fast on an unreachable server: a slow request here holds a PHP worker for every poll
        $request = fn (string $key) => $pool->as($key)->withHeaders($headers)->connectTimeout(3)->timeout(5);

        $requests['info'] = $request('info')->get("{$url}/api/v1/info");
        $requests['cpu'] = $request('cpu')->get("{$url}/api/v1/data?chart=system.cpu&{$commonParams}");
        $requests['ram'] = $request('ram')->get("{$url}/api/v1/data?chart=system.ram&{$commonParams}");

        foreach ($networks as $name => $data) {
            $requests["net_{$name}"] = $request("net_{$name}")->get("{$url}/api/v1/data?chart=net.{$name}&{$commonParams}");
        }

        return $requests;
    }

    protected function buildSystemInfo($response): array
    {
        if (! $response || ! $response->successful()) {
            return [];
        }

        $data = $response->json();
        $labels = $data['host_labels'] ?? [];

        return [
            'os' => ($data['os_name'] ?? '').' '.($data['os_version'] ?? ''),
            'os_id' => $data['os_id'] ?? $data['os_name'] ?? '',
            'hostname' => $labels['_hostname'] ?? 'Unknown',
            'ip' => $labels['_net_default_iface_ip'] ?? '-',
            'timezone' => $labels['_timezone'] ?? '',
            'kernel_name' => $data['kernel_name'] ?? 'Linux',
            'kernel_version' => $data['kernel_version'] ?? '',
            'architecture' => $data['architecture'] ?? '',
            'netdata_version' => $data['version'] ?? '-',
            'uid' => $data['uid'] ?? '-',
        ];
    }

    protected function buildCpuData(array $current, $historyResponse, $infoResponse = null): array
    {
        $cpuModel = null;
        $coresCount = null;

        if ($infoResponse && $infoResponse->successful()) {
            $infoData = $infoResponse->json();
            $rawModel = $infoData['host_labels']['_system_cpu_model'] ?? 'CPU';
            $coresCount = $infoData['host_labels']['_system_cores'] ?? '?';
            $cpuModel = $this->cleanCpuName($rawModel);
        }

        $dims = $current['dimensions'] ?? [];
        $user = $dims['user']['value'] ?? 0;
        $system = $dims['system']['value'] ?? 0;
        $iowait = $dims['iowait']['value'] ?? 0;
        $totalUsage = $user + $system + $iowait;

        $chartData = [];
        if ($historyResponse && $historyResponse->successful()) {
            $data = $historyResponse->json();
            $labels = $data['labels'] ?? [];
            $values = $data['data'] ?? [];

            $idxUser = array_search('user', $labels);
            $idxSystem = array_search('system', $labels);
            $idxIowait = array_search('iowait', $labels);

            foreach (array_reverse($values) as $point) {
                $u = ($idxUser !== false) ? ($point[$idxUser] ?? 0) : 0;
                $s = ($idxSystem !== false) ? ($point[$idxSystem] ?? 0) : 0;
                $i = ($idxIowait !== false) ? ($point[$idxIowait] ?? 0) : 0;
                $chartData[] = $u + $s + $i;
            }
        }

        return [
            'usage' => round($totalUsage, 1),
            'model' => $cpuModel,
            'cores' => $coresCount,
            'chart' => $chartData,
        ];
    }

    protected function buildMemoryData(array $current, $historyResponse): array
    {
        $dims = $current['dimensions'] ?? [];
        $free = $dims['free']['value'] ?? 0;
        $used = $dims['used']['value'] ?? 0;
        $cached = $dims['cached']['value'] ?? 0;
        $buffers = $dims['buffers']['value'] ?? 0;

        $total = $free + $used + $cached + $buffers;
        $multiplier = 1024 * 1024;

        $usedBytes = $used * $multiplier;
        $totalBytes = $total * $multiplier;

        $percent = $total > 0 ? ($used / $total) * 100 : 0;

        $chartData = [];
        if ($historyResponse && $historyResponse->successful()) {
            $data = $historyResponse->json();
            $labels = $data['labels'] ?? [];
            $values = $data['data'] ?? [];
            $idxUsed = array_search('used', $labels);

            foreach (array_reverse($values) as $point) {
                $chartData[] = ($idxUsed !== false) ? ($point[$idxUsed] ?? 0) : 0;
            }
        }

        return [
            'used_bytes' => $usedBytes,
            'total_bytes' => $totalBytes,
            'used_formatted' => Number::fileSize($usedBytes, 1),
            'total_formatted' => Number::fileSize($totalBytes, 1),
            'percent' => round($percent, 1),
            'chart' => $chartData,
        ];
    }

    protected function buildDiskData(array $disks): array
    {
        $stats = [];
        $multiplier = 1024 * 1024 * 1024;

        foreach ($disks as $name => $data) {
            $dims = $data['dimensions'] ?? [];
            $avail = $dims['avail']['value'] ?? 0;
            $used = $dims['used']['value'] ?? 0;
            $reserved = $dims['reserved_for_root']['value'] ?? 0;
            $total = $avail + $used + $reserved;

            $usedBytes = $used * $multiplier;
            $totalBytes = $total * $multiplier;
            $availBytes = $avail * $multiplier;

            $percent = $total > 0 ? round(($used / $total) * 100, 1) : 0;

            $stats[] = [
                'id' => $name,
                'name' => $data['family'] ?? $name,
                'used_bytes' => $usedBytes,
                'total_bytes' => $totalBytes,
                'free_bytes' => $availBytes,
                'used_formatted' => Number::fileSize($usedBytes, 1),
                'total_formatted' => Number::fileSize($totalBytes, 1),
                'free_formatted' => Number::fileSize($availBytes, 1),
                'percent' => $percent,
            ];
        }

        return $stats;
    }

    protected function buildNetworkData(array $networks, $historyResponses): array
    {
        $stats = [];

        foreach ($networks as $name => $data) {
            $response = $historyResponses["net_{$name}"] ?? null;

            if ($response && $response->successful()) {
                $rData = $response->json();
                $values = $rData['data'] ?? [];

                $latestRecv = isset($values[0]) ? round($values[0][1], 2) : 0; // kbps
                $latestSent = isset($values[0]) ? abs(round($values[0][2], 2)) : 0; // kbps

                $recBytes = $latestRecv * 1000 / 8;
                $sentBytes = $latestSent * 1000 / 8;

                $chartData = [];
                $rxChart = [];
                $txChart = [];
                foreach (array_reverse($values) as $point) {
                    $kbps = $point[1] + abs($point[2]);
                    $chartData[] = $kbps * 1000 / 8;
                    $rxChart[] = $point[1] * 1000 / 8;
                    $txChart[] = abs($point[2]) * 1000 / 8;
                }

                $stats[] = [
                    'name' => $name,
                    'rx_bytes' => $recBytes,
                    'tx_bytes' => $sentBytes,
                    'rx_formatted' => Number::fileSize($recBytes, 1).'/s',
                    'tx_formatted' => Number::fileSize($sentBytes, 1).'/s',
                    'chart' => $chartData,
                    'rx_chart' => $rxChart,
                    'tx_chart' => $txChart,
                ];
            }
        }

        return $stats;
    }

    /**
     * Round-trip time from Netdata's ping collector (charts ending in `_rtt`), if it has one.
     *
     * @param  array<string, mixed>  $allData
     * @return array{ms: float, host: string}|null
     */
    protected function buildPingData(array $allData): ?array
    {
        foreach ($allData as $key => $chart) {
            if (! str_ends_with($key, '_rtt') || str_starts_with($key, 'redis')) {
                continue;
            }

            $dimensions = $chart['dimensions'] ?? [];
            $average = $dimensions['avg']['value'] ?? collect($dimensions)->first()['value'] ?? null;

            if (is_numeric($average)) {
                return ['ms' => round((float) $average, 1), 'host' => (string) ($chart['family'] ?? $key)];
            }
        }

        return null;
    }

    protected function getFilteredDisks(Netdata $record, array $allData): array
    {
        $settings = $record->disk_settings ?? [];
        $disks = [];

        foreach ($allData as $key => $data) {
            if (str_starts_with($key, 'disk_space.')) {
                $name = $data['family'] ?? str_replace('disk_space.', '', $key);

                if (empty($settings) || in_array($name, $settings)) {
                    $disks[$key] = $data;
                }
            }
        }

        return $disks;
    }

    protected function getFilteredNetworks(Netdata $record, array $allData): array
    {
        $settings = $record->network_settings ?? [];
        $networks = [];

        foreach ($allData as $key => $data) {
            if (str_starts_with($key, 'net_speed.')) {
                $name = $data['family'] ?? str_replace('net_speed.', '', $key);

                if (empty($settings) || in_array($name, $settings)) {
                    $networks[$name] = $data;
                }
            }
        }

        return $networks;
    }

    protected function getBaseUrl(Netdata $record): string
    {
        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';

        return "https://{$hostname}{$path}";
    }

    protected function getAuthHeaders(Netdata $record): array
    {
        return [
            'cf-access-client-id' => $record->access?->client_id,
            'cf-access-client-secret' => $record->access?->client_secret,
        ];
    }

    protected function cleanCpuName(string $fullName): string
    {
        $replacements = [
            '/\(R\)/i' => '',
            '/\(TM\)/i' => '',
            '/Core /i' => '',
            '/\d+th Gen /i' => '',
            '/\d+rd Gen /i' => '',
            '/  +/' => ' ',
        ];

        return trim(preg_replace(array_keys($replacements), array_values($replacements), $fullName));
    }
}
