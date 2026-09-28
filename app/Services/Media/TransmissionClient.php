<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TransmissionClient extends MediaClient
{
    /**
     * Transmission answers any RPC call without a session id with a 409 that carries
     * one, so a 409 with that header proves the URL and credentials are right.
     */
    public function checkConnection(): bool
    {
        try {
            $response = Http::timeout(10)->withHeaders($this->authHeaders())->post($this->rpcUrl(), ['method' => 'session-get']);
        } catch (ConnectionException) {
            return false;
        }

        return $response->successful()
            || ($response->status() === 409 && $response->header('X-Transmission-Session-Id') !== '');
    }

    protected function authHeaders(): array
    {
        return ['Authorization' => 'Basic '.base64_encode($this->service->username.':'.$this->service->password)];
    }

    private function rpcUrl(): string
    {
        return $this->baseUrl.($this->service->settings['rpc_path'] ?? '/transmission/rpc');
    }

    public function getData(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget($this->service->cacheKey('data'));
        }

        return $this->remember($this->service->cacheKey('data'), [10, 86400], function () {
            try {
                $rpcUrl = $this->rpcUrl();
                $auth = base64_encode($this->service->username.':'.$this->service->password);

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
                    throw MediaFetchFailed::for($this->service);
                }

                $headers = [
                    'Authorization' => 'Basic '.$auth,
                    'X-Transmission-Session-Id' => $sessionId,
                ];

                // Get session stats
                $statsResponse = Http::timeout(10)->withHeaders($headers)->throw()
                    ->withOptions(['verify' => true])
                    ->post($rpcUrl, ['method' => 'session-stats'])
                    ->json();

                // Get all torrents
                $torrentsResponse = Http::timeout(10)->withHeaders($headers)->throw()
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
                    'name' => $this->service->name,
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
                    'url' => $this->baseUrl,
                ];
            } catch (\Exception $e) {
                throw MediaFetchFailed::for($this->service, $e);
            }
        });
    }
}
