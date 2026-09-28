<?php

namespace App\Services;

use App\Enums\MediaServiceType;
use App\Models\MediaService;
use App\Services\Media\JellyfinClient;
use App\Services\Media\RadarrClient;
use App\Services\Media\SonarrClient;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Entry point for the dashboard's media data. There can be several services
 * of one type (two Jellyfins, two torrent clients, ...); each has its own
 * client, cache keys and widget.
 */
class MediaArrService
{
    /** @var Collection<int, MediaService>|null */
    private ?Collection $services = null;

    /**
     * Enabled media services, keyed by id, in a stable order (type, then id).
     *
     * @return Collection<int, MediaService>
     */
    public function services(): Collection
    {
        return $this->services ??= MediaService::query()
            ->enabled()
            ->orderBy('type')
            ->orderBy('id')
            ->get()
            ->keyBy('id');
    }

    public function find(int $id): ?MediaService
    {
        return $this->services()->get($id);
    }

    /**
     * @return Collection<int, MediaService>
     */
    public function ofType(MediaServiceType $type): Collection
    {
        return $this->services()->filter(fn (MediaService $service): bool => $service->type === $type);
    }

    /**
     * Fresh or cached (stale-while-revalidate) data for one service. Null when it has none yet.
     */
    public function data(MediaService $service, bool $fresh = false): ?array
    {
        $client = $service->client();

        return $client instanceof JellyfinClient
            ? $client->getData(freshSessions: $fresh)
            : $client->getData($fresh);
    }

    /**
     * Whatever is cached for a service, without calling it. For an instant first paint.
     */
    public function cachedData(MediaService $service): ?array
    {
        $client = $service->client();

        return $client instanceof JellyfinClient
            ? $client->getCachedData()
            : Cache::get($service->cacheKey('data'));
    }

    /**
     * Upcoming movies and episodes from every enabled Radarr and Sonarr.
     *
     * @return list<array<string, mixed>>
     */
    public function getAgendaData(bool $fresh = false): array
    {
        $sources = $this->ofType(MediaServiceType::Radarr)->merge($this->ofType(MediaServiceType::Sonarr));

        if ($sources->isEmpty()) {
            return [];
        }

        if ($fresh) {
            Cache::forget('media_agenda');
        }

        try {
            return Cache::flexible('media_agenda', [60, 86400], fn (): array => $sources
                ->flatMap(function (MediaService $service): array {
                    /** @var RadarrClient|SonarrClient $client */
                    $client = $service->client();

                    return $client->upcoming();
                })
                ->filter(fn ($i) => $i['date'] !== null)
                ->sortBy('date')
                ->values()
                ->toArray());
        } catch (HttpClientException $e) {
            report($e);

            return Cache::get('media_agenda', []);
        }
    }
}
