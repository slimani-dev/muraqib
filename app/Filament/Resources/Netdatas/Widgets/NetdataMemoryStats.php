<?php

namespace App\Filament\Resources\Netdatas\Widgets;

use App\Models\Netdata;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Number;

class NetdataMemoryStats extends BaseWidget
{
    public ?Netdata $record = null;

    protected ?string $pollingInterval = '2s';

    protected int|string|array $columnSpan = 1;

    protected int | array | null $columns = 1;

    protected function getStats(): array
    {
        $record = $this->record;

        if (! $record) {
            return [];
        }

        $hostname = $record->ingressRule?->hostname;
        $path = $record->ingressRule?->path ?? '';
        $url = "https://{$hostname}{$path}";

        try {
            // Fetch RAM Data
            // system.ram units are typically MB
            $apiUrl = "{$url}/api/v1/data?chart=system.ram&points=20&format=json&after=-20&options=unaligned";

            $response = Http::withHeaders([
                'cf-access-client-id' => $record->access?->client_id,
                'cf-access-client-secret' => $record->access?->client_secret,
            ])->timeout(2)->get($apiUrl);

            if (! $response->successful()) {
                return [Stat::make('Memory', 'Offline')->color('danger')];
            }

            $data = $response->json();
            $labels = $data['labels'] ?? [];
            $values = $data['data'] ?? []; // Newest first

            $idxUsed = array_search('used', $labels);
            $idxFree = array_search('free', $labels);
            $idxCached = array_search('cached', $labels);
            $idxBuffers = array_search('buffers', $labels);

            // Latest Values (MB)
            $latestPoint = $values[0] ?? [];
            $usedMB = ($idxUsed !== false) ? ($latestPoint[$idxUsed] ?? 0) : 0;
            $freeMB = ($idxFree !== false) ? ($latestPoint[$idxFree] ?? 0) : 0;
            $cachedMB = ($idxCached !== false) ? ($latestPoint[$idxCached] ?? 0) : 0;
            $buffersMB = ($idxBuffers !== false) ? ($latestPoint[$idxBuffers] ?? 0) : 0;

            // Total = Used + Free + Cached + Buffers
            $totalMB = $usedMB + $freeMB + $cachedMB + $buffersMB;

            // Convert to Bytes for formatting (MB -> B)
            $usedBytes = $usedMB * 1024 * 1024;
            $totalBytes = $totalMB * 1024 * 1024;
            $freeBytes = ($freeMB + $cachedMB + $buffersMB) * 1024 * 1024; // "Available" usually treats cached/buffers as available

            $percent = $totalMB > 0 ? ($usedMB / $totalMB) * 100 : 0;

            $formattedUsed = Number::fileSize($usedBytes, 1);
            $formattedTotal = Number::fileSize($totalBytes, 1);

            // Chart Data (Used Memory History)
            $chartData = [];
            foreach (array_reverse($values) as $point) {
                $u = ($idxUsed !== false) ? ($point[$idxUsed] ?? 0) : 0;
                $chartData[] = $u;
            }

            $color = match (true) {
                $percent > 90 => 'danger',
                $percent > 70 => 'warning',
                default => 'success',
            };

            return [
                Stat::make('Memory', "{$formattedUsed} / {$formattedTotal}")
                    ->description(number_format($percent, 1).'% Used')
                    ->icon('heroicon-m-rectangle-stack') // Or mdi-memory if preferred, sticking to heroicons for consistency
                    ->chart($chartData)
                    ->color($color),
            ];

        } catch (\Exception $e) {
            return [Stat::make('Memory', 'Error')->color('danger')];
        }
    }
}
