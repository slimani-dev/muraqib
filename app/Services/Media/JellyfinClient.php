<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Illuminate\Support\Facades\Cache;

class JellyfinClient extends MediaClient
{
    public function checkConnection(): bool
    {
        return $this->respondsSuccessfully('/System/Info');
    }

    protected function authHeaders(): array
    {
        return ['X-Emby-Token' => (string) $this->service->api_key];
    }

    public function getData(bool $freshSessions = false, bool $freshStatic = false): ?array
    {
        if ($freshSessions) {
            Cache::forget($this->service->cacheKey('sessions'));
        }
        if ($freshStatic) {
            Cache::forget($this->service->cacheKey('static'));
        }

        $sessions = $this->remember($this->service->cacheKey('sessions'), [60, 86400], function () {
            try {
                return $this->request()
                    ->get("{$this->baseUrl}/Sessions")
                    ->json();
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });

        $staticData = $this->remember($this->service->cacheKey('static'), [300, 86400], function () {
            try {
                $usersResponse = $this->request()
                    ->get("{$this->baseUrl}/Users")
                    ->json();

                $users = collect($usersResponse)->filter(fn ($u) => ! ($u['Policy']['IsDisabled'] ?? false))->values();

                $recentlyPlayedByUser = [];
                foreach ($users as $user) {
                    $items = $this->request()
                        ->get("{$this->baseUrl}/Users/{$user['Id']}/Items", [
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
                            'image' => "{$this->baseUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=400",
                            'backdrop' => "{$this->baseUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                            'type' => $item['Type'],
                            'id' => $item['Id'],
                            'mediaUrl' => "{$this->baseUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
                            'progress' => isset($item['UserData']['PlaybackPositionTicks']) && isset($item['RunTimeTicks'])
                                ? round(($item['UserData']['PlaybackPositionTicks'] / $item['RunTimeTicks']) * 100)
                                : 0,
                            'played' => $item['UserData']['Played'] ?? false,
                        ];
                    })->toArray();
                }

                $nextUpByUser = [];
                foreach ($users as $user) {
                    $items = $this->request()
                        ->get("{$this->baseUrl}/Shows/NextUp", [
                            'UserId' => $user['Id'],
                            'Limit' => 15,
                            'Fields' => 'PrimaryImageAspectRatio,DateCreated,Overview',
                        ])->json()['Items'] ?? [];

                    $nextUpByUser[$user['Id']] = collect($items)->map(function ($item) {
                        return [
                            'title' => $item['Name'],
                            'seriesName' => $item['SeriesName'] ?? null,
                            'episode' => $item['Type'] === 'Episode' ? 'S'.str_pad($item['ParentIndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT).'E'.str_pad($item['IndexNumber'] ?? 1, 2, '0', STR_PAD_LEFT) : ($item['ProductionYear'] ?? ''),
                            'image' => "{$this->baseUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=400",
                            'backdrop' => "{$this->baseUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                            'type' => $item['Type'],
                            'id' => $item['Id'],
                            'mediaUrl' => "{$this->baseUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
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
                    'avatar' => ($u['HasPrimaryImage'] ?? false) ? "{$this->baseUrl}/Users/{$u['Id']}/Images/Primary?tag=".($u['PrimaryImageTag'] ?? '') : null,
                ])->toArray();

                return [
                    'users' => $formattedUsers,
                    'recentlyPlayedByUser' => $recentlyPlayedByUser,
                    'nextUpByUser' => $nextUpByUser,
                ];

            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });

        if ($sessions === null || $staticData === null) {
            return null;
        }

        return $this->formatData($sessions, $staticData);
    }

    public function getCachedData(): ?array
    {
        $sessions = Cache::get($this->service->cacheKey('sessions'));
        $staticData = Cache::get($this->service->cacheKey('static'));

        if ($sessions === null || $staticData === null) {
            return null;
        }

        return $this->formatData($sessions, $staticData);
    }

    private function formatData(array $sessions, array $staticData): array
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
                'image' => "{$this->baseUrl}/Items/{$item['Id']}/Images/Primary?maxWidth=800",
                'backdrop' => "{$this->baseUrl}/Items/".($item['SeriesId'] ?? $item['Id']).'/Images/Thumb?fillHeight=240&fillWidth=426',
                'progress' => isset($nowPlaying['PlayState']['PositionTicks']) && isset($item['RunTimeTicks'])
                    ? round(($nowPlaying['PlayState']['PositionTicks'] / $item['RunTimeTicks']) * 100)
                    : 0,
                'user' => $nowPlaying['UserName'],
                'mediaUrl' => "{$this->baseUrl}/web/index.html#!/details?id={$item['Id']}".(isset($item['ServerId']) ? "&serverId={$item['ServerId']}" : ''),
            ];
        }

        return [
            'name' => $this->service->name,
            'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/jellyfin.png',
            'status' => 'Running',
            'url' => $this->baseUrl,
            'nowPlaying' => $formattedNowPlaying,
            'users' => $staticData['users'] ?? [],
            'recentlyPlayedByUser' => $staticData['recentlyPlayedByUser'] ?? [],
            'nextUpByUser' => $staticData['nextUpByUser'] ?? [],
        ];
    }
}
