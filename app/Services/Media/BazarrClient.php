<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Illuminate\Support\Facades\Cache;

class BazarrClient extends MediaClient
{
    public function checkConnection(): bool
    {
        return $this->respondsSuccessfully('/api/system/status');
    }

    protected function authHeaders(): array
    {
        return ['X-API-KEY' => (string) $this->service->api_key];
    }

    public function getData(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget($this->service->cacheKey('data'));
        }

        return $this->remember($this->service->cacheKey('data'), [10, 86400], function () {
            try {
                $statusResponse = $this->request()
                    ->get("{$this->baseUrl}/api/system/status")->json();

                $providersResponse = $this->request()
                    ->get("{$this->baseUrl}/api/providers")->json();

                $moviesResponse = $this->request()
                    ->get("{$this->baseUrl}/api/movies")->json();

                // Newer Bazarr versions need IDs for /api/episodes; /api/series has per-series counts.
                $seriesResponse = $this->request()
                    ->get("{$this->baseUrl}/api/series")->json();

                $providers = $providersResponse['data'] ?? [];
                $healthyProviders = collect($providers)->filter(fn ($p) => $p['status'] === 'Good')->count();
                $totalProviders = count($providers);

                $moviesData = $moviesResponse['data'] ?? [];
                $totalMovies = count($moviesData);
                $missingMovies = collect($moviesData)->filter(fn ($m) => count($m['missing_subtitles'] ?? []) > 0)->count();

                $seriesData = collect($seriesResponse['data'] ?? []);
                $totalEpisodes = $seriesData->sum('episodeFileCount');
                $missingEpisodes = $seriesData->sum('episodeMissingCount');

                return [
                    'name' => $this->service->name,
                    'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/bazarr.png',
                    'status' => isset($statusResponse['data']) ? 'Running' : 'Offline',
                    'stats' => [
                        ['label' => 'Missing (Movies)', 'value' => $missingMovies.' / '.$totalMovies, 'color' => 'text-yellow-500'],
                        ['label' => 'Missing (Episodes)', 'value' => $missingEpisodes.' / '.$totalEpisodes, 'color' => 'text-yellow-500'],
                        ['label' => 'Healthy Providers', 'value' => $healthyProviders.' / '.$totalProviders, 'color' => 'text-emerald-500'],
                    ],
                    'url' => $this->baseUrl,
                ];
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });
    }
}
