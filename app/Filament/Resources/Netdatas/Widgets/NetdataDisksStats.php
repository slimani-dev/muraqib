<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Number;

class NetdataDisksStats extends BaseWidget
{
    public ?Netdata $record = null;

    protected ?string $pollingInterval = '20s';

    protected int|string|array $columnSpan = 1;

    protected int | array | null $columns = 1;

    protected $listeners = ['refresh-netdata-disks' => '$refresh'];

    protected function getStats(): array
    {
        $record = $this->record;

        if (! $record) {
            return [];
        }

        // Fetch Data
        try {
            $hostname = $record->ingressRule?->hostname;
            $path = $record->ingressRule?->path ?? '';
            $url = "https://{$hostname}{$path}";
            $apiUrl = "{$url}/api/v1/allmetrics?format=json&filter=disk_space.*&help=no&types=no&timings=no";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(2)->get($apiUrl);

            if (! $response->successful()) {
                return [Stat::make('Status', 'Offline')->color('danger')];
            }

            $data = $response->json();
            $stats = [];

            // Get saved settings to filter disks
            $savedSettings = $record->disk_settings ?? [];
            $multiplier = 1024 * 1024 * 1024; // GiB assumed

            foreach ($data as $key => $diskData) {
                $name = $diskData['family'] ?? $key;

                // Filter if settings exist
                if (! empty($savedSettings) && ! in_array($name, $savedSettings)) {
                    continue;
                }

                $dims = $diskData['dimensions'] ?? [];
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

                $stats[] = Stat::make($name, "Free: {$formattedAvail}")
                    ->view('filament.resources.netdatas.widgets.disk-progress', [
                        'percent' => $percent,
                        'progressColor' => $color,
                    ])
                    ->icon('mdi-harddisk')
                    ->color($color)
                    ->description(new HtmlString("<span class='text-nowrap whitespace-nowrap text-gray-500'>{$formattedUsed} / {$formattedTotal} </span>"));
            }

            return $stats;

        } catch (\Exception $e) {
            return [Stat::make('Error', 'Connection Failed')->color('danger')];
        }
    }
}
