<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class MediaArrService
{
    private string $jellyfinUrl;

    private string $jellyfinKey;

    private string $jellyfinUserId;

    private string $seerrUrl;

    private string $seerrKey;

    private string $radarrUrl;

    private string $radarrKey;

    private string $sonarrUrl;

    private string $sonarrKey;

    private string $bazarrUrl;

    private string $bazarrKey;

    private string $transmissionUrl;

    private string $transmissionRpcPath;

    private string $transmissionUser;

    private string $transmissionPass;

    public function __construct()
    {
        $this->jellyfinUrl = rtrim((string) config('services.media.jellyfin.url'), '/');
        $this->jellyfinKey = (string) config('services.media.jellyfin.key');
        $this->jellyfinUserId = (string) config('services.media.jellyfin.user_id');
        $this->seerrUrl = rtrim((string) config('services.media.seerr.url'), '/');
        $this->seerrKey = (string) config('services.media.seerr.key');
        $this->radarrUrl = rtrim((string) config('services.media.radarr.url'), '/');
        $this->radarrKey = (string) config('services.media.radarr.key');
        $this->sonarrUrl = rtrim((string) config('services.media.sonarr.url'), '/');
        $this->sonarrKey = (string) config('services.media.sonarr.key');
        $this->bazarrUrl = rtrim((string) config('services.media.bazarr.url'), '/');
        $this->bazarrKey = (string) config('services.media.bazarr.key');
        $this->transmissionUrl = rtrim((string) config('services.media.transmission.url'), '/');
        $this->transmissionRpcPath = (string) config('services.media.transmission.rpc_path', '/transmission/rpc');
        $this->transmissionUser = (string) config('services.media.transmission.username');
        $this->transmissionPass = (string) config('services.media.transmission.password');
    }

    public function getJellyfinData(bool $freshSessions = false, bool $freshStatic = false): array
    {
        if ($freshSessions) {
            Cache::forget('jellyfin_sessions');
        }
        if ($freshStatic) {
            Cache::forget('jellyfin_static');
        }

        $sessions = Cache::flexible('jellyfin_sessions', [60, 86400], function () {
            try {
                return Http::timeout(3)->withHeaders(['X-Emby-Token' => $this->jellyfinKey])
                    ->get("{$this->jellyfinUrl}/Sessions")
                    ->json();
            } catch (\Exception $e) {
                return [];
            }
        });

        $staticData = Cache::flexible('jellyfin_static', [300, 86400], function () {
            try {
                $usersResponse = Http::timeout(3)->withHeaders(['X-Emby-Token' => $this->jellyfinKey])
                    ->get("{$this->jellyfinUrl}/Users")
                    ->json();

                $users = collect($usersResponse)->filter(fn ($u) => ! ($u['Policy']['IsDisabled'] ?? false))->values();

                $recentlyPlayedByUser = [];
                foreach ($users as $user) {
                    $items = Http::timeout(3)->withHeaders(['X-Emby-Token' => $this->jellyfinKey])
                        ->get("{$this->jellyfinUrl}/Users/{$user['Id']}/Items", [
                            'SortBy' => 'DatePlayed',
                            'SortOrder' => 'Descending',
                            'Filters' => 'IsResumable',
                            'Limit' => 15,
                            'Recursive' => 'true',
                            'IncludeItemTypes' => 'Movie,Episode',
                            'Fields' => 'PrimaryImageAspectRatio,DateCreated',
                        ])->json()['Items'] ?? [];

                    $recentlyPlayedByUser[$user['Id']] = collect($items)->map(function ($item) {
                        return [
                            'title' => $item['Name'],
                            'seriesName' => $item['SeriesName'] ?? null,
                            'episode' => $item['Type'] === 'Episode' ? 'S'.str_pad($item['ParentIndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT).'E'.str_pad($item['IndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT) : ($item['ProductionYear'] ?? ''),
                            'image' => "{$this->jellyfinUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=400",
                            'backdrop' => "{$this->jellyfinUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                            'type' => $item['Type'],
                            'id' => $item['Id'],
                            'mediaUrl' => "{$this->jellyfinUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
                            'progress' => isset($item['UserData']['PlaybackPositionTicks']) && isset($item['RunTimeTicks'])
                                ? round(($item['UserData']['PlaybackPositionTicks'] / $item['RunTimeTicks']) * 100)
                                : 0,
                            'played' => $item['UserData']['Played'] ?? false,
                        ];
                    })->toArray();
                }

                $nextUpByUser = [];
                foreach ($users as $user) {
                    $items = Http::timeout(3)->withHeaders(['X-Emby-Token' => $this->jellyfinKey])
                        ->get("{$this->jellyfinUrl}/Shows/NextUp", [
                            'UserId' => $user['Id'],
                            'Limit' => 15,
                            'Fields' => 'PrimaryImageAspectRatio,DateCreated,Overview',
                        ])->json()['Items'] ?? [];

                    $nextUpByUser[$user['Id']] = collect($items)->map(function ($item) {
                        return [
                            'title' => $item['Name'],
                            'seriesName' => $item['SeriesName'] ?? null,
                            'episode' => $item['Type'] === 'Episode' ? 'S'.str_pad($item['ParentIndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT).'E'.str_pad($item['IndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT) : ($item['ProductionYear'] ?? ''),
                            'image' => "{$this->jellyfinUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=400",
                            'backdrop' => "{$this->jellyfinUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                            'type' => $item['Type'],
                            'id' => $item['Id'],
                            'mediaUrl' => "{$this->jellyfinUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
                            'progress' => isset($item['UserData']['PlaybackPositionTicks']) && isset($item['RunTimeTicks'])
                                ? round(($item['UserData']['PlaybackPositionTicks'] / $item['RunTimeTicks']) * 100)
                                : 0,
                            'played' => $item['UserData']['Played'] ?? false,
                        ];
                    })->toArray();
                }

                $formattedUsers = $users->map(fn ($u) => [
                    'id' => $u['Id'],
                    'name' => $u['Name'],
                    'avatar' => ($u['HasPrimaryImage'] ?? false) ? "{$this->jellyfinUrl}/Users/{$u['Id']}/Images/Primary?tag=".($u['PrimaryImageTag'] ?? '') : null,
                ])->toArray();

                return [
                    'users' => $formattedUsers,
                    'recentlyPlayedByUser' => $recentlyPlayedByUser,
                    'nextUpByUser' => $nextUpByUser,
                ];

            } catch (\Exception $e) {
                return [
                    'users' => [],
                    'recentlyPlayedByUser' => [],
                    'nextUpByUser' => [],
                ];
            }
        });

        return $this->formatJellyfinData($sessions, $staticData);
    }

    public function getJellyfinCachedData(): ?array
    {
        $sessions = Cache::get('jellyfin_sessions');
        $staticData = Cache::get('jellyfin_static');

        if ($sessions === null || $staticData === null) {
            return null;
        }

        return $this->formatJellyfinData($sessions, $staticData);
    }

    private function formatJellyfinData(array $sessions, array $staticData): array
    {
        // Find all streaming sessions
        $nowPlayingSessions = collect($sessions)->filter(fn ($s) => isset($s['NowPlayingItem']));

        $formattedNowPlaying = [];
        foreach ($nowPlayingSessions as $nowPlaying) {
            $item = $nowPlaying['NowPlayingItem'];
            $episodeStr = $item['Type'] === 'Episode' ? 'S'.str_pad($item['ParentIndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT).'E'.str_pad($item['IndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT) : ($item['ProductionYear'] ?? '');

            $formattedNowPlaying[$nowPlaying['UserId']] = [
                'id' => $item['Id'],
                'title' => $item['Name'],
                'details' => $item['Type'] === 'Episode' ? $item['SeriesName'] : '',
                'episode' => $episodeStr,
                'device' => $nowPlaying['DeviceName'] ?? $nowPlaying['Client'] ?? '',
                'image' => "{$this->jellyfinUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=800",
                'backdrop' => "{$this->jellyfinUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                'progress' => isset($nowPlaying['PlayState']['PositionTicks']) && isset($item['RunTimeTicks'])
                    ? round(($nowPlaying['PlayState']['PositionTicks'] / $item['RunTimeTicks']) * 100)
                    : 0,
                'user' => $nowPlaying['UserName'],
                'mediaUrl' => "{$this->jellyfinUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
            ];
        }

        return [
            'name' => 'Jellyfin',
            'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/jellyfin.png',
            'status' => 'Running',
            'url' => $this->jellyfinUrl,
            'nowPlaying' => $formattedNowPlaying,
            'users' => $staticData['users'] ?? [],
            'recentlyPlayedByUser' => $staticData['recentlyPlayedByUser'] ?? [],
            'nextUpByUser' => $staticData['nextUpByUser'] ?? [],
        ];
    }

    public function getSeerrData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('seerr_data');
        }

        return Cache::flexible('seerr_data', [10, 86400], function () {
            try {
                // Recently Added
                $recentlyAddedResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->seerrKey])
                    ->get("{$this->seerrUrl}/api/v1/media", [
                        'filter' => 'available',
                        'take' => 10,
                        'sort' => 'mediaAdded',
                    ])->json();

                // Recent Requests
                $recentRequestsResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->seerrKey])
                    ->get("{$this->seerrUrl}/api/v1/request", [
                        'filter' => 'all',
                        'take' => 15,
                        'sort' => 'added',
                    ])->json();
            } catch (\Exception $e) {
                $recentlyAddedResponse = ['results' => []];
                $recentRequestsResponse = ['results' => []];
            }

            $formattedNewShows = collect($recentlyAddedResponse['results'] ?? [])->map(function ($media) {
                try {
                    $isMovie = $media['mediaType'] === 'movie';
                    $detailsEndpoint = $isMovie ? "/api/v1/movie/{$media['tmdbId']}" : "/api/v1/tv/{$media['tmdbId']}";

                    // Fetch basic details for title and poster
                    $details = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->seerrKey])
                        ->get("{$this->seerrUrl}{$detailsEndpoint}")
                        ->json();

                    $year = $isMovie
                        ? substr($details['releaseDate'] ?? '', 0, 4)
                        : substr($details['firstAirDate'] ?? '', 0, 4);

                    // To get requested user, we could look at the requests attached to media,
                    // or just supply a placeholder/unknown if not available in this endpoint.
                    // The media endpoint in Overseerr usually includes mediaInfo if requested.

                    return [
                        'title' => $details['title'] ?? $details['name'] ?? 'Unknown',
                        'description' => $details['overview'] ?? null,
                        'status' => ($media['status'] ?? 0) === 5 ? 'Available' : 'Partially Available',
                        'image' => isset($details['posterPath']) ? "https://image.tmdb.org/t/p/w600_and_h900_bestv2{$details['posterPath']}" : null,
                        'backdrop' => isset($details['backdropPath']) ? "https://image.tmdb.org/t/p/w1920_and_h800_multi_faces{$details['backdropPath']}" : null,
                        'type' => $media['mediaType'],
                        'year' => $year,
                        'tmdbId' => $media['tmdbId'],
                        // Static placeholders for now, could be fetched via full request info
                        'userName' => 'moh',
                        'userAvatar' => 'https://ui-avatars.com/api/?name=moh&background=random',
                        'season' => ! $isMovie ? '1' : null,
                        'mediaUrl' => $media['mediaUrl'] ?? null,
                        'serviceUrl' => $media['serviceUrl'] ?? null,
                        'seerrUrl' => "{$this->seerrUrl}/".($isMovie ? 'movie' : 'tv')."/{$media['tmdbId']}",
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter()->toArray();

            $formattedRequests = collect($recentRequestsResponse['results'] ?? [])->map(function ($req) {
                try {
                    $media = $req['media'];
                    $isMovie = ($media['mediaType'] ?? '') === 'movie';
                    $detailsEndpoint = $isMovie ? "/api/v1/movie/{$media['tmdbId']}" : "/api/v1/tv/{$media['tmdbId']}";

                    $details = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->seerrKey])
                        ->get("{$this->seerrUrl}{$detailsEndpoint}")
                        ->json();

                    $year = $isMovie
                        ? substr($details['releaseDate'] ?? '', 0, 4)
                        : substr($details['firstAirDate'] ?? '', 0, 4);

                    $statusMap = [
                        1 => 'Pending',
                        2 => 'Approved',
                        3 => 'Declined',
                        4 => 'Processing',
                        5 => 'Available',
                    ];
                    $statusName = $statusMap[$req['status'] ?? 0] ?? 'Unknown';

                    return [
                        'title' => $details['title'] ?? $details['name'] ?? $media['title'] ?? $media['name'] ?? 'Unknown',
                        'status' => $statusName,
                        'image' => isset($details['posterPath']) ? "https://image.tmdb.org/t/p/w600_and_h900_bestv2{$details['posterPath']}" : null,
                        'backdrop' => isset($details['backdropPath']) ? "https://image.tmdb.org/t/p/w1920_and_h800_multi_faces{$details['backdropPath']}" : null,
                        'type' => $isMovie ? 'movie' : 'tv',
                        'year' => $year,
                        'tmdbId' => $media['tmdbId'] ?? null,
                        'userName' => $req['requestedBy']['displayName'] ?? 'Unknown',
                        'userAvatar' => isset($req['requestedBy']['avatar'])
                            ? (str_starts_with($req['requestedBy']['avatar'], 'http') ? $req['requestedBy']['avatar'] : "{$this->seerrUrl}{$req['requestedBy']['avatar']}")
                            : 'https://ui-avatars.com/api/?name='.urlencode($req['requestedBy']['displayName'] ?? 'U').'&background=random',
                        'season' => ! $isMovie && isset($req['seasons']) && count($req['seasons']) > 0 ? implode(', ', array_map(function ($s) {
                            return $s['seasonNumber'];
                        }, $req['seasons'])) : null,
                        'mediaUrl' => $media['mediaUrl'] ?? null,
                        'serviceUrl' => $media['serviceUrl'] ?? null,
                        'seerrUrl' => "{$this->seerrUrl}/".($isMovie ? 'movie' : 'tv')."/{$media['tmdbId']}",
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter(function ($req) {
                return $req !== null; // Keep all valid requests
            })->values()->toArray();

            return [
                'name' => 'Seerr',
                'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/seerr.png',
                'status' => 'Running',
                'requests' => $formattedRequests,
                'newShows' => $formattedNewShows,
                'totalRequests' => $recentRequestsResponse['pageInfo']['results'] ?? 0,
                'totalMedia' => $recentlyAddedResponse['pageInfo']['results'] ?? 0,
                'url' => $this->seerrUrl,
            ];
        });
    }

    public function getRadarrData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('radarr_data');
        }

        return Cache::flexible('radarr_data', [10, 86400], function () {
            try {
                $moviesResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->radarrKey])
                    ->get("{$this->radarrUrl}/api/v3/movie")->json();

                $healthResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->radarrKey])
                    ->get("{$this->radarrUrl}/api/v3/health")->json();

                $queueResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->radarrKey])
                    ->get("{$this->radarrUrl}/api/v3/queue")->json();

                $rootFolderResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->radarrKey])
                    ->get("{$this->radarrUrl}/api/v3/rootfolder")->json();

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
                    'name' => 'Radarr',
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
                    'url' => $this->radarrUrl,
                ];
            } catch (\Exception $e) {
                return ['queue' => ['downloading' => 0, 'failed' => 0, 'importing' => 0], 'stats' => []];
            }
        });
    }

    public function getSonarrData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('sonarr_data');
        }

        return Cache::flexible('sonarr_data', [10, 86400], function () {
            try {
                $seriesResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/series")->json();

                $healthResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/health")->json();

                $queueResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/queue")->json();

                $missingResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/wanted/missing", ['pageSize' => 1])->json();

                $rootFolderResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/rootfolder")->json();

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
                    'name' => 'Sonarr',
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
                    'url' => $this->sonarrUrl,
                ];
            } catch (\Exception $e) {
                return ['queue' => ['downloading' => 0, 'failed' => 0, 'importing' => 0], 'stats' => []];
            }
        });
    }

    public function getBazarrData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('bazarr_data');
        }

        return Cache::flexible('bazarr_data', [10, 86400], function () {
            try {
                $statusResponse = Http::timeout(3)->withHeaders(['X-API-KEY' => $this->bazarrKey])
                    ->get("{$this->bazarrUrl}/api/system/status")->json();

                $providersResponse = Http::timeout(3)->withHeaders(['X-API-KEY' => $this->bazarrKey])
                    ->get("{$this->bazarrUrl}/api/providers")->json();

                $moviesResponse = Http::timeout(3)->withHeaders(['X-API-KEY' => $this->bazarrKey])
                    ->get("{$this->bazarrUrl}/api/movies")->json();

                $episodesResponse = Http::timeout(3)->withHeaders(['X-API-KEY' => $this->bazarrKey])
                    ->get("{$this->bazarrUrl}/api/episodes")->json();

                $providers = $providersResponse['data'] ?? [];
                $healthyProviders = collect($providers)->filter(fn ($p) => $p['status'] === 'Good')->count();
                $totalProviders = count($providers);

                $moviesData = $moviesResponse['data'] ?? [];
                $totalMovies = count($moviesData);
                $missingMovies = collect($moviesData)->filter(fn ($m) => count($m['missing_subtitles'] ?? []) > 0)->count();

                $episodesData = $episodesResponse['data'] ?? [];
                $totalEpisodes = count($episodesData);
                $missingEpisodes = collect($episodesData)->filter(fn ($e) => count($e['missing_subtitles'] ?? []) > 0)->count();

                return [
                    'name' => 'Bazaarr',
                    'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/bazarr.png',
                    'status' => isset($statusResponse['data']) ? 'Running' : 'Offline',
                    'stats' => [
                        ['label' => 'Missing (Movies)', 'value' => $missingMovies.' / '.$totalMovies, 'color' => 'text-yellow-500'],
                        ['label' => 'Missing (Episodes)', 'value' => $missingEpisodes.' / '.$totalEpisodes, 'color' => 'text-yellow-500'],
                        ['label' => 'Healthy Providers', 'value' => $healthyProviders.' / '.$totalProviders, 'color' => 'text-emerald-500'],
                    ],
                    'url' => $this->bazarrUrl,
                ];
            } catch (\Exception $e) {
                return ['status' => 'Offline', 'stats' => []];
            }
        });
    }

    public function getTransmissionData(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget('transmission_data');
        }

        return Cache::flexible('transmission_data', [10, 86400], function () {
            try {
                $rpcUrl = $this->transmissionUrl.$this->transmissionRpcPath;
                $auth = base64_encode($this->transmissionUser.':'.$this->transmissionPass);

                // Transmission requires a CSRF token (X-Transmission-Session-Id).
                // First request will return 409 with the token in the header.
                $initialResponse = Http::timeout(3)
                    ->withHeaders([
                        'Authorization' => 'Basic '.$auth,
                    ])
                    ->withOptions(['verify' => true])
                    ->post($rpcUrl, [
                        'method' => 'session-get',
                    ]);

                $sessionId = $initialResponse->header('X-Transmission-Session-Id');

                if (! $sessionId) {
                    return ['status' => 'Offline', 'stats' => []];
                }

                $headers = [
                    'Authorization' => 'Basic '.$auth,
                    'X-Transmission-Session-Id' => $sessionId,
                ];

                // Get session stats
                $statsResponse = Http::timeout(3)->withHeaders($headers)
                    ->withOptions(['verify' => true])
                    ->post($rpcUrl, ['method' => 'session-stats'])
                    ->json();

                // Get all torrents
                $torrentsResponse = Http::timeout(3)->withHeaders($headers)
                    ->withOptions(['verify' => true])
                    ->post($rpcUrl, [
                        'method' => 'torrent-get',
                        'arguments' => [
                            'fields' => [
                                'id', 'name', 'status', 'rateDownload', 'rateUpload',
                                'percentDone', 'totalSize', 'sizeWhenDone', 'eta',
                                'peersConnected', 'peersSendingToUs', 'peersGettingFromUs',
                                'uploadRatio', 'addedDate', 'error', 'errorString',
                                'downloadedEver', 'uploadedEver',
                            ],
                        ],
                    ])
                    ->json();

                $stats = $statsResponse['arguments'] ?? [];
                $torrents = collect($torrentsResponse['arguments']['torrents'] ?? []);

                $downloading = $torrents->filter(fn ($t) => $t['status'] === 4)->count();
                $seeding = $torrents->filter(fn ($t) => $t['status'] === 6)->count();
                $paused = $torrents->filter(fn ($t) => $t['status'] === 0)->count();
                $total = $torrents->count();

                $downSpeed = $stats['downloadSpeed'] ?? 0;
                $upSpeed = $stats['uploadSpeed'] ?? 0;

                // Status map: 0=stopped, 1=check-wait, 2=checking, 3=dl-wait, 4=downloading, 5=seed-wait, 6=seeding
                $statusLabels = [
                    0 => 'Paused',
                    1 => 'Queued',
                    2 => 'Checking',
                    3 => 'Queued',
                    4 => 'Downloading',
                    5 => 'Queued',
                    6 => 'Seeding',
                ];

                $torrentItems = $torrents->map(function ($t) use ($statusLabels) {
                    $eta = $t['eta'] ?? -1;
                    $etaStr = match (true) {
                        $eta < 0 => null,
                        $eta < 60 => $eta.'s',
                        $eta < 3600 => round($eta / 60).'m',
                        $eta < 86400 => round($eta / 3600, 1).'h',
                        default => round($eta / 86400, 1).'d',
                    };

                    return [
                        'id' => $t['id'],
                        'name' => $t['name'],
                        'status' => $statusLabels[$t['status']] ?? 'Unknown',
                        'statusCode' => $t['status'],
                        'progress' => round(($t['percentDone'] ?? 0) * 100, 1),
                        'size' => $this->formatBytes($t['totalSize'] ?? $t['sizeWhenDone'] ?? 0, 1),
                        'downloaded' => $this->formatBytes($t['downloadedEver'] ?? (($t['totalSize'] ?? 0) * ($t['percentDone'] ?? 0)), 1),
                        'uploaded' => $this->formatBytes($t['uploadedEver'] ?? 0, 1),
                        'downSpeed' => $this->formatSpeed($t['rateDownload'] ?? 0),
                        'upSpeed' => $this->formatSpeed($t['rateUpload'] ?? 0),
                        'eta' => $etaStr,
                        'peers' => $t['peersConnected'] ?? 0,
                        'seeders' => $t['peersGettingFromUs'] ?? 0,
                        'leechers' => $t['peersSendingToUs'] ?? 0,
                        'ratio' => ($t['uploadRatio'] ?? 0) > 0 ? number_format((float) ($t['uploadRatio']), 2) : '0.00',
                        'hasError' => ($t['error'] ?? 0) > 0,
                        'errorString' => $t['errorString'] ?? null,
                    ];
                })->sortByDesc('statusCode')->values()->all();

                return [
                    'name' => 'Transmission',
                    'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/transmission.png',
                    'status' => 'Running',
                    'speeds' => [
                        'down' => $this->formatSpeed($downSpeed),
                        'up' => $this->formatSpeed($upSpeed),
                    ],
                    'downloading' => $downloading,
                    'seeding' => $seeding,
                    'paused' => $paused,
                    'total' => $total,
                    'torrents' => $torrentItems,
                    'stats' => [
                        ['label' => 'Active', 'value' => $downloading, 'color' => 'text-primary'],
                        ['label' => 'Seeding', 'value' => $seeding, 'color' => 'text-chart-3'],
                        ['label' => 'Paused', 'value' => $paused, 'color' => 'text-yellow-500'],
                        ['label' => 'Total', 'value' => $total, 'color' => 'text-foreground'],
                    ],
                    'url' => $this->transmissionUrl,
                ];
            } catch (\Exception $e) {
                return ['status' => 'Offline', 'stats' => [], 'torrents' => []];
            }
        });
    }

    public function getAgendaData(): array
    {
        return Cache::flexible('media_agenda', [60, 86400], function () {
            try {
                $start = Carbon::now()->format('Y-m-d');
                $end = Carbon::now()->addDays(30)->format('Y-m-d');

                $radarrResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->radarrKey])
                    ->get("{$this->radarrUrl}/api/v3/calendar", [
                        'start' => $start,
                        'end' => $end,
                    ])->json();

                $sonarrResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/calendar", [
                        'start' => $start,
                        'end' => $end,
                    ])->json();

                $sonarrSeriesResponse = Http::timeout(3)->withHeaders(['X-Api-Key' => $this->sonarrKey])
                    ->get("{$this->sonarrUrl}/api/v3/series")->json();

                $sonarrSeriesMap = collect($sonarrSeriesResponse ?? [])->keyBy('id');

                $radarrItems = collect($radarrResponse ?? [])->filter(fn ($u) => ! ($u['hasFile'] ?? false))->map(function ($u) {
                    $date = $u['digitalRelease'] ?? $u['physicalRelease'] ?? $u['inCinemas'] ?? null;

                    return [
                        'id' => 'radarr_'.($u['id'] ?? uniqid()),
                        'type' => 'movie',
                        'title' => $u['title'] ?? 'Unknown',
                        'date' => $date ? Carbon::parse($date)->format('Y-m-d') : null,
                        'hasFile' => $u['hasFile'] ?? false,
                        'poster' => collect($u['images'] ?? [])->firstWhere('coverType', 'poster')['remoteUrl'] ?? null,
                    ];
                });

                $sonarrItems = collect($sonarrResponse ?? [])->filter(fn ($u) => ! ($u['hasFile'] ?? false))->map(function ($u) use ($sonarrSeriesMap) {
                    $series = $sonarrSeriesMap->get($u['seriesId']) ?? [];

                    return [
                        'id' => 'sonarr_'.($u['id'] ?? uniqid()),
                        'type' => 'series',
                        'title' => ($series['title'] ?? 'Unknown Series'),
                        'episodeInfo' => 'S'.str_pad($u['seasonNumber'] ?? 0, 2, '0', STR_PAD_LEFT).'E'.str_pad($u['episodeNumber'] ?? 0, 2, '0', STR_PAD_LEFT).' - '.($u['title'] ?? 'Unknown'),
                        'date' => isset($u['airDateUtc']) ? Carbon::parse($u['airDateUtc'])->format('Y-m-d') : null,
                        'hasFile' => $u['hasFile'] ?? false,
                        'poster' => collect($series['images'] ?? [])->firstWhere('coverType', 'poster')['remoteUrl'] ?? null,
                    ];
                });

                return $radarrItems->merge($sonarrItems)->filter(fn ($i) => $i['date'] !== null)->sortBy('date')->values()->toArray();
            } catch (\Exception $e) {
                return [];
            }
        });
    }

    private function formatSpaceRatio($used, $free)
    {
        $usedStr = $this->formatBytes($used, 0);
        $freeStr = $this->formatBytes($free, 0);

        $usedParts = explode(' ', $usedStr);
        $freeParts = explode(' ', $freeStr);

        if (count($usedParts) == 2 && count($freeParts) == 2 && $usedParts[1] === $freeParts[1]) {
            return $usedParts[0].' / '.$freeStr;
        }

        return $usedStr.' / '.$freeStr;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    private function formatSpeed(int $bytesPerSecond): string
    {
        if ($bytesPerSecond < 1024) {
            return $bytesPerSecond.' B/s';
        }

        if ($bytesPerSecond < 1048576) {
            return round($bytesPerSecond / 1024, 1).' KB/s';
        }

        return round($bytesPerSecond / 1048576, 1).' MB/s';
    }
}
