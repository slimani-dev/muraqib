<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\Widget;

class NetdataStatsOverview extends Widget
{
    protected string $view = 'filament.resources.netdatas.widgets.netdata-stats-overview';

    protected int|string|array $columnSpan = 1;

    public ?Netdata $record = null;

    public array $widgets = [];

    public function mount(): void
    {
        $this->widgets = [
            \App\Filament\Resources\Netdatas\Widgets\NetdataCpuStats::class,
            \App\Filament\Resources\Netdatas\Widgets\NetdataMemoryStats::class,
            \App\Filament\Resources\Netdatas\Widgets\NetdataNetworkStats::class,
            \App\Filament\Resources\Netdatas\Widgets\NetdataDisksStats::class,
        ];
    }
}
