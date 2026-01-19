<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\Widget;

class NetdataStatsOverview extends Widget
{
    protected string $view = 'filament.resources.netdatas.widgets.netdata-stats-overview';

    protected int|string|array $columnSpan = 1;

    public ?Netdata $record = null;
}
