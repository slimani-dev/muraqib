<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class SonarrClient extends MediaClient
{
    public function checkConnection(): bool
    {
        return $this->respondsSuccessfully('/api/v3/system/status');
    }

    public function getData(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget($this->service->cacheKey('data'));
        }

        return $this->remember($this->service->cacheKey('data'), [10, 86400], function () {
            try {
                $seriesResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/series")->json();

                $healthResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/health")->json();

                $queueResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/queue")->json();

                $missingResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/wanted/missing", ['pageSize' => 1])->json();

                $rootFolderResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/rootfolder")->json();

                $totalSeries = is_array($seriesResponse) ? count($seriesResponse) : 0;
                $monitoredSeries = is_array($seriesResponse) ? collect($seriesResponse)->filter(fn ($s) => $s['monitored'] ?? false)->count() : 0;
                $totalEpisodes = is_array($seriesResponse) ? collect($seriesResponse)->sum(fn ($s) => $s['statistics']['episodeCount'] ?? 0) : 0;
                $usedSpace = is_array($seriesResponse) ? collect($seriesResponse)->sum(fn ($s) => $s['statistics']['sizeOnDisk'] ?? 0) : 0;

                $missingEpisodes = $missingResponse['totalRecords'] ?? 0;
                $warnings = is_array($healthResponse) ? collect($healthResponse)->filter(fn ($h) => $h['type'] === 'warning' || $h['type'] === 'error')->count() : 0;

                $freeSpace = isset($rootFolderResponse[0]['freeSpace']) ? $rootFolderResponse[0]['freeSpace'] : 0;

                $queueRecords = $queueResponse['records'] ?? [];
                $downloading = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'downloading')->count();
                $failed = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'warning' || $q['status'] === 'failed')->count();
                $importing = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'completed')->count(); // Completed but in queue means importing

                return [
                    'name' => $this->service->name,
                    'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/sonarr.png',
                    'status' => $warnings > 0 ? 'Warning' : 'Running',
                    'warnings' => $warnings,
                    'queue' => [
                        'downloading' => $downloading,
                        'failed' => $failed,
                        'importing' => $importing,
                    ],
                    'stats' => [
                        ['label' => 'Missing', 'value' => $missingEpisodes.'/'.$totalEpisodes, 'color' => 'text-yellow-500'],
                        ['label' => 'Monitored', 'value' => $monitoredSeries.'/'.$totalSeries, 'color' => 'text-primary'],
                        ['label' => 'Space (Used/Free)', 'value' => $this->formatSpaceRatio($usedSpace, $freeSpace), 'color' => 'text-chart-2'],
                    ],
                    'url' => $this->baseUrl,
                ];
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });
    }

    /**
     * Episodes without a file airing in the next 30 days, for the media agenda.
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(): array
    {
        $sonarrResponse = $this->request()
            ->get("{$this->baseUrl}/api/v3/calendar", [
                'start' => Carbon::now()->format('Y-m-d'),
                'end' => Carbon::now()->addDays(30)->format('Y-m-d'),
            ])->json();

        $sonarrSeriesResponse = $this->request()
            ->get("{$this->baseUrl}/api/v3/series")->json();

        $sonarrSeriesMap = collect($sonarrSeriesResponse ?? [])->keyBy('id');

        return collect($sonarrResponse ?? [])->filter(fn ($u) => ! ($u['hasFile'] ?? false))->map(function ($u) use ($sonarrSeriesMap) {
            $series = $sonarrSeriesMap->get($u['seriesId']) ?? [];

            return [
                'id' => "sonarr_{$this->service->id}_".($u['id'] ?? uniqid()),
                'type' => 'series',
                'title' => ($series['title'] ?? 'Unknown Series'),
                'episodeInfo' => 'S'.str_pad($u['seasonNumber'] ?? 0, 2, '0', STR_PAD_LEFT).'E'.str_pad($u['episodeNumber'] ?? 0, 2, '0', STR_PAD_LEFT).' - '.($u['title'] ?? 'Unknown'),
                'date' => isset($u['airDateUtc']) ? Carbon::parse($u['airDateUtc'])->format('Y-m-d') : null,
                'hasFile' => $u['hasFile'] ?? false,
                'poster' => collect($series['images'] ?? [])->firstWhere('coverType', 'poster')['remoteUrl'] ?? null,
            ];
        })->values()->all();
    }
}
