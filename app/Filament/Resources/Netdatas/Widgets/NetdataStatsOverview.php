<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use App\Services\NetdataService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

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

        $service = app(NetdataService::class);
        $data = $service->getStats($this->record);

        if (! $data) {
            return [Stat::make('Status', 'Offline')->color('danger')->icon('heroicon-m-signal-slash')];
        }

        $stats = [];

        // 1. System Info
        $stats[] = Stat::make($data['info']['os'], $data['info']['hostname'])
            ->description($data['info']['ip'].' • '.$data['info']['timezone'])
            ->icon($this->getOsIcon($data['info']['os_id']))
            ->color('primary');

        $stats[] = Stat::make($data['info']['kernel_name'], "Kernel {$data['info']['kernel_version']}")
            ->description($data['info']['architecture'])
            ->icon($this->getKernelIcon($data['info']['kernel_name']))
            ->color('gray');

        $stats[] = Stat::make('Netdata', $data['info']['netdata_version'])
            ->description(new HtmlString('<span class="text-xs truncate block max-w-[150px]" title="'.$data['info']['uid'].'">'.$data['info']['uid'].'</span>'))
            ->icon('si-netdata')
            ->color('info');

        // 2. CPU
        $cpuColor = match (true) {
            $data['cpu']['usage'] > 80 => 'danger',
            $data['cpu']['usage'] > 50 => 'warning',
            default => 'success',
        };

        $label = $data['cpu']['cores'] ? "CPU ({$data['cpu']['cores']} Cores)" : 'CPU Usage';
        $value = $data['cpu']['model'] ?? number_format($data['cpu']['usage'], 1).'%';
        $desc = $data['cpu']['model'] ? number_format($data['cpu']['usage'], 1).'%' : '';

        $stats[] = Stat::make($label, $value)
            ->description($desc)
            ->icon('heroicon-m-cpu-chip')
            ->chart($data['cpu']['chart'])
            ->color($cpuColor);

        // 3. Memory
        $memColor = match (true) {
            $data['memory']['percent'] > 90 => 'danger',
            $data['memory']['percent'] > 70 => 'warning',
            default => 'success',
        };

        $stats[] = Stat::make('Memory', "{$data['memory']['used_formatted']} / {$data['memory']['total_formatted']}")
            ->description(number_format($data['memory']['percent'], 1).'% Used')
            ->icon('heroicon-m-rectangle-stack')
            ->chart($data['memory']['chart'])
            ->color($memColor);

        // 4. Network
        foreach ($data['networks'] as $network) {
            $color = ($network['rx_bytes'] > 125000 || $network['tx_bytes'] > 125000) ? 'success' : 'gray';

            $icon = match (true) {
                str_starts_with($network['name'], 'w') => 'heroicon-m-wifi',
                str_starts_with($network['name'], 'vmbr') => 'heroicon-m-rectangle-stack',
                str_starts_with($network['name'], 'veth') => 'heroicon-m-cube',
                str_starts_with($network['name'], 'docker') => 'heroicon-m-cube',
                default => 'heroicon-m-arrows-right-left',
            };

            $stats[] = Stat::make($network['name'], "↓ {$network['rx_formatted']} / ↑ {$network['tx_formatted']}")
                ->icon($icon)
                ->chart($network['chart'])
                ->color($color);
        }

        // 5. Disks
        foreach ($data['disks'] as $disk) {
            $color = match (true) {
                $disk['percent'] >= 90 => 'danger',
                $disk['percent'] >= 70 => 'warning',
                default => 'success',
            };

            $stats[] = Stat::make("{$disk['name']} ({$disk['percent']}% used)", "Free: {$disk['free_formatted']}")
                ->view('filament.resources.netdatas.widgets.disk-progress', [
                    'percent' => $disk['percent'],
                    'progressColor' => $color,
                    'description' => $disk['name'],
                ])
                ->icon('mdi-harddisk')
                ->color($color)
                ->description(new HtmlString("<span class='text-nowrap whitespace-nowrap text-gray-500'>{$disk['used_formatted']} / {$disk['total_formatted']}</span>"));
        }

        return $stats;
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
}
