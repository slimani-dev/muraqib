<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Illuminate\Support\Facades\Cache;

class SeerrClient extends MediaClient
{
    public function checkConnection(): bool
    {
        return $this->respondsSuccessfully('/api/v1/settings/main');
    }

    public function getData(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget($this->service->cacheKey('data'));
        }

        return $this->remember($this->service->cacheKey('data'), [10, 86400], function () {
            try {
                // Recently Added
                $recentlyAddedResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v1/media", [
                        'filter' => 'available',
                        'take' => 10,
                        'sort' => 'mediaAdded',
                    ])->json();

                // Recent Requests
                $recentRequestsResponse = $this->request()
                    ->get("{$this->baseUrl}/api/v1/request", [
                        'filter' => 'all',
                        'take' => 15,
                        'sort' => 'added',
                    ])->json();
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }

            $formattedNewShows = collect($recentlyAddedResponse['results'] ?? [])->map(function ($media) {
                try {
                    $isMovie = $media['mediaType'] === 'movie';
                    $detailsEndpoint = $isMovie ? "/api/v1/movie/{$media['tmdbId']}" : "/api/v1/tv/{$media['tmdbId']}";

                    // Fetch basic details for title and poster
                    $details = $this->request()
                        ->get("{$this->baseUrl}{$detailsEndpoint}")
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
                        // Who asked for it, when Seerr includes the request with the media
                        'userName' => $media['requests'][0]['requestedBy']['displayName'] ?? null,
                        'userAvatar' => $media['requests'][0]['requestedBy']['avatar'] ?? null,
                        'season' => ! $isMovie ? '1' : null,
                        'mediaUrl' => $media['mediaUrl'] ?? null,
                        'serviceUrl' => $media['serviceUrl'] ?? null,
                        'seerrUrl' => "{$this->baseUrl}/".($isMovie ? 'movie' : 'tv')."/{$media['tmdbId']}",
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

                    $details = $this->request()
                        ->get("{$this->baseUrl}{$detailsEndpoint}")
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
                            ? (str_starts_with($req['requestedBy']['avatar'], 'http') ? $req['requestedBy']['avatar'] : "{$this->baseUrl}{$req['requestedBy']['avatar']}")
                            : 'https://ui-avatars.com/api/?name='.urlencode($req['requestedBy']['displayName'] ?? 'U').'&background=random',
                        'season' => ! $isMovie && isset($req['seasons']) && count($req['seasons']) > 0 ? implode(', ', array_map(function ($s) {
                            return $s['seasonNumber'];
                        }, $req['seasons'])) : null,
                        'mediaUrl' => $media['mediaUrl'] ?? null,
                        'serviceUrl' => $media['serviceUrl'] ?? null,
                        'seerrUrl' => "{$this->baseUrl}/".($isMovie ? 'movie' : 'tv')."/{$media['tmdbId']}",
                    ];
                } catch (\Exception $e) {
                    return null;
                }
            })->filter(function ($req) {
                return $req !== null; // Keep all valid requests
            })->values()->toArray();

            return [
                'name' => $this->service->name,
                'icon' => 'https://raw.githubusercontent.com/walkxcode/dashboard-icons/main/png/seerr.png',
                'status' => 'Running',
                'requests' => $formattedRequests,
                'newShows' => $formattedNewShows,
                'totalRequests' => $recentRequestsResponse['pageInfo']['results'] ?? 0,
                'totalMedia' => $recentlyAddedResponse['pageInfo']['results'] ?? 0,
                'url' => $this->baseUrl,
            ];
        });
    }
}
