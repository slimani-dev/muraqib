<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class RadarrClient extends MediaClient
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
                $moviesResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/movie")->json();

                $healthResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/health")->json();

                $queueResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/queue")->json();

                $rootFolderResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v3/rootfolder")->json();

                $totalMovies = is_array($moviesResponse) ? count($moviesResponse) : 0;
                $missingMovies = is_array($moviesResponse) ? collect($moviesResponse)->filter(fn ($m) => ! ($m['hasFile'] ?? false))->count() : 0;
                $monitoredMovies = is_array($moviesResponse) ? collect($moviesResponse)->filter(fn ($m) => $m['monitored'] ?? false)->count() : 0;
                $usedSpace = is_array($moviesResponse) ? collect($moviesResponse)->sum('sizeOnDisk') : 0;

                $freeSpace = isset($rootFolderResponse[0]['freeSpace']) ? $rootFolderResponse[0]['freeSpace'] : 0;

                $warnings = is_array($healthResponse) ? collect($healthResponse)->filter(fn ($h) => $h['type'] === 'warning' || $h['type'] === 'error')->count() : 0;

                $queueRecords = $queueResponse['records'] ?? [];
                $downloading = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'downloading')->count();
                $failed = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'warning' || $q['status'] === 'failed')->count();
                $importing = collect($queueRecords)->filter(fn ($q) => $q['status'] === 'completed')->count(); // Completed but in queue means importing

                return [
                    'name' => $this->service->name,
                    'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/radarr.png',
                    'status' => $warnings > 0 ? 'Warning' : 'Running',
                    'warnings' => $warnings,
                    'queue' => [
                        'downloading' => $downloading,
                        'failed' => $failed,
                        'importing' => $importing,
                    ],
                    'stats' => [
                        ['label' => 'Missing', 'value' => $missingMovies, 'color' => 'text-yellow-500'],
                        ['label' => 'Monitored', 'value' => $monitoredMovies.'/'.$totalMovies, 'color' => 'text-primary'],
                        ['label' => 'Space (Used/Free)', 'value' => $this->formatSpaceRatio($usedSpace, $freeSpace), 'color' => 'text-chart-4'],
                    ],
                    'url' => $this->baseUrl,
                ];
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });
    }

    /**
     * Movies without a file releasing in the next 30 days, for the media agenda.
     *
     * @return list<array<string, mixed>>
     */
    public function upcoming(): array
    {
        $radarrResponse = $this->request()
            ->get("{$this->baseUrl}/api/v3/calendar", [
                'start' => Carbon::now()->format('Y-m-d'),
                'end' => Carbon::now()->addDays(30)->format('Y-m-d'),
            ])->json();

        return collect($radarrResponse ?? [])->filter(fn ($u) => ! ($u['hasFile'] ?? false))->map(function ($u) {
            $date = $u['digitalRelease'] ?? $u['physicalRelease'] ?? $u['inCinemas'] ?? null;

            return [
                'id' => "radarr_{$this->service->id}_".($u['id'] ?? uniqid()),
                'type' => 'movie',
                'title' => $u['title'] ?? 'Unknown',
                'date' => $date ? Carbon::parse($date)->format('Y-m-d') : null,
                'hasFile' => $u['hasFile'] ?? false,
                'poster' => collect($u['images'] ?? [])->firstWhere('coverType', 'poster')['remoteUrl'] ?? null,
            ];
        })->values()->all();
    }
}
