<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class NetdataStatsOverview extends BaseWidget
{
    public ?Netdata $record = null;

    protected ?string $pollingInterval = '2s';

    protected int|string|array $columnSpan = 'full';

    protected int|array|null $columns = [
        'default' => 1,
        'md' => 2,
        'xl' => 3,
        '2xl' => 4,
    ];

    protected $listeners = [
        'refresh-netdata-layout' => '$refresh',
        'refresh-netdata-disks' => '$refresh',
        'refresh-netdata-network' => '$refresh',
    ];

    protected function getStats(): array
    {
        if (! $this->record) {
            return [];
        }

        $url = $this->getBaseUrl();
        $headers = $this->getAuthHeaders();

        // 1. Fetch current snapshot of everything (CPU, RAM, Disks, Networks)
        // detailed filter to get what we need in one go
        $filter = 'system.cpu system.ram disk_space.* net_speed.*';
        $allMetricsUrl = "{$url}/api/v1/allmetrics?format=json&help=no&types=no&timings=no&filter=".urlencode($filter);

        try {
            $response = Http::withHeaders($headers)->timeout(5)->get($allMetricsUrl);

            if (! $response->successful()) {
                return [Stat::make('Status', 'Offline')->color('danger')->icon('heroicon-m-signal-slash')];
            }

            $allData = $response->json();

            // Prepare list of interfaces and disks to process
            $disksToProcess = $this->getFilteredDisks($allData);
            $networksToProcess = $this->getFilteredNetworks($allData);

            // 2. Fetch History Data in Parallel (CPU, RAM, Net Interfaces) AND System Info
            $historyResponses = Http::pool(fn (Pool $pool) => $this->preparePoolRequests($pool, $url, $headers, $networksToProcess));

            // 3. Construct Stats
            return array_merge(
                $this->buildSystemInfoStats($historyResponses['info'] ?? null),
                $this->buildCpuStat($allData['system.cpu'] ?? [], $historyResponses['cpu'] ?? null, $historyResponses['info'] ?? null),
                $this->buildMemoryStat($allData['system.ram'] ?? [], $historyResponses['ram'] ?? null),
                $this->buildNetworkStats($networksToProcess, $historyResponses),
                $this->buildDiskStats($disksToProcess)
            );

        } catch (\Exception $e) {
            return [Stat::make('Error', 'Connection Failed')->description($e->getMessage())->color('danger')];
        }
    }

    protected function preparePoolRequests(Pool $pool, string $url, array $headers, array $networks): array
    {
        $requests = [];
        $commonParams = 'points=20&format=json&after=-20&options=unaligned';

        // System Info
        $requests['info'] = $pool->as('info')->withHeaders($headers)->get("{$url}/api/v1/info");

        // CPU History
        $requests['cpu'] = $pool->as('cpu')->withHeaders($headers)->get("{$url}/api/v1/data?chart=system.cpu&{$commonParams}");

        // RAM History
        $requests['ram'] = $pool->as('ram')->withHeaders($headers)->get("{$url}/api/v1/data?chart=system.ram&{$commonParams}");

        // Network Interfaces History
        foreach ($networks as $name => $data) {
            $requests["net_{$name}"] = $pool->as("net_{$name}")->withHeaders($headers)
                ->get("{$url}/api/v1/data?chart=net.{$name}&{$commonParams}");
        }

        return $requests;
    }

    protected function buildSystemInfoStats($response): array
    {
        if (! $response || ! $response->successful()) {
            return [];
        }

        $data = $response->json();
        $labels = $data['host_labels'] ?? [];

        // 1. System Info
        $host = $labels['_hostname'] ?? 'Unknown';
        $ip = $labels['_net_default_iface_ip'] ?? '-';
        $os = ($data['os_name'] ?? '').' '.($data['os_version'] ?? '');
        $osId = $data['os_id'] ?? $data['os_name'] ?? '';

        // 2. Hardware / Kernel
        $kernelName = $data['kernel_name'] ?? 'Linux';
        $kernel = $data['kernel_version'] ?? '';
        $arch = $data['architecture'] ?? '';
        $timezone = $labels['_timezone'] ?? '';

        // 3. Agent Info
        $version = $data['version'] ?? '-';
        $uid = $data['uid'] ?? '-';

        return [
            Stat::make($os, $host)
                ->description($ip.' • '.$timezone)
                ->icon($this->getOsIcon($osId))
                ->color('primary'),

            Stat::make($kernelName, "Kernel {$kernel}")
                ->description($arch)
                ->icon($this->getKernelIcon($kernelName))
                ->color('gray'),

            Stat::make('Netdata', $version)
                ->description(new HtmlString('<span class="text-xs truncate block max-w-[150px]" title="'.$uid.'">'.$uid.'</span>'))
                ->icon('si-netdata')
                ->color('info'),
        ];
    }

    protected function getOsIcon(string $osId): string
    {
        return match (strtolower($osId)) {
            'ubuntu' => 'si-ubuntu',
            'debian' => 'si-debian',
            'centos' => 'si-centos',
            'fedora' => 'si-fedora',
            'redhat', 'rhel' => 'si-redhat',
            'suse', 'opensuse' => 'si-opensuse',
            'arch', 'archlinux' => 'si-archlinux',
            'alpine' => 'si-alpinelinux',
            'freebsd' => 'si-freebsd',
            'gentoo' => 'si-gentoo',
            'linux mint', 'mint' => 'si-linuxmint',
            'manjaro' => 'si-manjaro',
            'windows' => 'si-windows',
            'macos', 'darwin', 'apple' => 'si-apple',
            'linux' => 'si-linux',
            'proxmox', 'pve' => 'si-proxmox',
            default => 'heroicon-m-server',
        };
    }

    protected function getKernelIcon(string $kernelName): string
    {
        return match (strtolower($kernelName)) {
            'linux' => 'si-linux',
            'windows', 'mingw', 'msys' => 'si-windows',
            'darwin', 'macos' => 'si-apple',
            'freebsd' => 'si-freebsd',
            default => 'heroicon-m-cpu-chip',
        };
    }

    protected function buildCpuStat(array $current, $historyResponse, $infoResponse = null): array
    {
        // CPU Info
        $cpuModel = null;
        $cores = null;

        if ($infoResponse && $infoResponse->successful()) {
            $infoData = $infoResponse->json();
            $rawModel = $infoData['host_labels']['_system_cpu_model'] ?? 'CPU';
            $rawCores = $infoData['host_labels']['_system_cores'] ?? '?';

            $cpuModel = $this->cleanCpuName($rawModel);
            $cores = "({$rawCores} Cores)";
        }

        // Current Load
        $dims = $current['dimensions'] ?? [];
        $user = $dims['user']['value'] ?? 0;
        $system = $dims['system']['value'] ?? 0;
        $iowait = $dims['iowait']['value'] ?? 0;
        $totalUsage = $user + $system + $iowait;

        // Chart Data
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

        $color = match (true) {
            $totalUsage > 80 => 'danger',
            $totalUsage > 50 => 'warning',
            default => 'success',
        };

        $label = $cores ? "CPU {$cores}" : 'CPU Usage';
        $value = $cpuModel ?? number_format($totalUsage, 1).'%';
        $desc = $cpuModel ? number_format($totalUsage, 1).'%' : '';

        return [
            Stat::make($label, $value)
                ->description($desc)
                ->icon('heroicon-m-cpu-chip')
                ->chart($chartData)
                ->color($color),
        ];
    }

    protected function buildMemoryStat(array $current, $historyResponse): array
    {
        $dims = $current['dimensions'] ?? [];
        $free = $dims['free']['value'] ?? 0;
        $used = $dims['used']['value'] ?? 0;
        $cached = $dims['cached']['value'] ?? 0;
        $buffers = $dims['buffers']['value'] ?? 0;

        $total = $free + $used + $cached + $buffers;
        $multiplier = 1024 * 1024; // MiB -> Bytes

        $usedBytes = $used * $multiplier;
        $totalBytes = $total * $multiplier;
        $formattedUsed = Number::fileSize($usedBytes, 1);
        $formattedTotal = Number::fileSize($totalBytes, 1);

        $percent = $total > 0 ? ($used / $total) * 100 : 0;

        // Chart Data
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

        $color = match (true) {
            $percent > 90 => 'danger',
            $percent > 70 => 'warning',
            default => 'success',
        };

        return [
            Stat::make('Memory', "{$formattedUsed} / {$formattedTotal}")
                ->description(number_format($percent, 1).'% Used')
                ->icon('heroicon-m-rectangle-stack')
                ->chart($chartData)
                ->color($color),
        ];
    }

    protected function buildDiskStats(array $disks): array
    {
        $stats = [];
        $multiplier = 1024 * 1024 * 1024; // GiB

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
            $formattedUsed = Number::fileSize($usedBytes, 1);
            $formattedTotal = Number::fileSize($totalBytes, 1);
            $formattedAvail = Number::fileSize($availBytes, 1);

            $color = match (true) {
                $percent >= 90 => 'danger',
                $percent >= 70 => 'warning',
                default => 'success',
            };

            // Clean name
            $cleanName = $data['family'] ?? $name;

            $stats[] = Stat::make("{$cleanName} ({$percent}% used)", "Free: {$formattedAvail}")
                ->view('filament.resources.netdatas.widgets.disk-progress', [
                    'percent' => $percent,
                    'progressColor' => $color,
                    'description' => $cleanName, // Pass name if view needs it, though Stats usually handle title
                ])
                ->icon('mdi-harddisk')
                ->color($color)
                ->description(new HtmlString("<span class='text-nowrap whitespace-nowrap text-gray-500'>{$formattedUsed} / {$formattedTotal}</span>"));
        }

        return $stats;
    }

    protected function buildNetworkStats(array $networks, $historyResponses): array
    {
        $stats = [];

        foreach ($networks as $name => $data) {
            // Speed from allmetrics is instantaneous
            $dims = $data['dimensions'] ?? [];
            // net_speed usually has just one dimension "speed" which is sum of in/out or just raw value?
            // Actually net_speed charts in Netdata are often just one value or require checking net.eth0 for split.
            // But we requested net_speed.*. Let's rely on history for accurate Recv/Sent split and sparkline.

            $response = $historyResponses["net_{$name}"] ?? null;

            if ($response && $response->successful()) {
                $rData = $response->json();
                $values = $rData['data'] ?? [];

                $latestRecv = isset($values[0]) ? round($values[0][1], 2) : 0; // kbps
                $latestSent = isset($values[0]) ? abs(round($values[0][2], 2)) : 0; // kbps

                $recBytes = $latestRecv * 1000 / 8;
                $sentBytes = $latestSent * 1000 / 8;

                $recFormatted = Number::fileSize($recBytes, 1).'/s';
                $sentFormatted = Number::fileSize($sentBytes, 1).'/s';

                $chartData = [];
                foreach (array_reverse($values) as $point) {
                    $chartData[] = $point[1] + abs($point[2]);
                }

                $color = ($latestRecv > 1000 || $latestSent > 1000) ? 'success' : 'gray';

                $icon = match (true) {
                    str_starts_with($name, 'w') => 'heroicon-m-wifi',
                    str_starts_with($name, 'vmbr') => 'heroicon-m-rectangle-stack',
                    str_starts_with($name, 'veth') => 'heroicon-m-cube',
                    str_starts_with($name, 'docker') => 'heroicon-m-cube',
                    default => 'heroicon-m-arrows-right-left',
                };

                $stats[] = Stat::make($name, "↓ {$recFormatted} / ↑ {$sentFormatted}")
                    ->icon($icon)
                    ->chart($chartData)
                    ->color($color);
            }
        }

        return $stats;
    }

    protected function getFilteredDisks(array $allData): array
    {
        $settings = $this->record->disk_settings ?? [];
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

    protected function getFilteredNetworks(array $allData): array
    {
        $settings = $this->record->network_settings ?? [];
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

    protected function getBaseUrl(): string
    {
        $hostname = $this->record->ingressRule?->hostname;
        $path = $this->record->ingressRule?->path ?? '';

        return "https://{$hostname}{$path}";
    }

    protected function getAuthHeaders(): array
    {
        return [
            'cf-access-client-id' => $this->record->access?->client_id,
            'cf-access-client-secret' => $this->record->access?->client_secret,
        ];
    }

    protected function cleanCpuName(string $fullName): string
    {
        $replacements = [
            '/\(R\)/i' => '',
            '/\(TM\)/i' => '',
            '/Core /i' => '',
            '/\d+th Gen /i' => '', // Requires removing 11th Gen, 12th Gen, etc.
            '/\d+rd Gen /i' => '',
            '/  +/' => ' ', // Remove double spaces
        ];

        $cleanName = preg_replace(array_keys($replacements), array_values($replacements), $fullName);

        return trim($cleanName);
    }
}
