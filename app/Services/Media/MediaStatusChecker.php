<?php

namespace App\Services\Media;

use App\Enums\MediaServiceStatus;
use App\Models\MediaService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side accessibility check, for status checks the browser can't run
 * (secret headers, plain-http URLs). Browser checks live in useStatusCheck.ts.
 */
class MediaStatusChecker
{
    /** Seconds a result is reused, so dashboard polls don't multiply requests. */
    public const CACHE_SECONDS = 60;

    public function status(MediaService $service): ?MediaServiceStatus
    {
        if ($service->statusCheckUrl() === null) {
            return null;
        }

        $status = Cache::remember(
            $service->statusCacheKey(),
            self::CACHE_SECONDS,
            fn (): string => $this->check($service)->value,
        );

        return MediaServiceStatus::from($status);
    }

    public function check(MediaService $service): MediaServiceStatus
    {
        $url = $service->statusCheckUrl();

        if ($url === null) {
            return MediaServiceStatus::Down;
        }

        try {
            $response = Http::timeout(5)
                ->withHeaders($service->status_headers ?? [])
                ->get($url);
        } catch (ConnectionException) {
            return MediaServiceStatus::Down;
        }

        return match (true) {
            $response->status() < 400 => MediaServiceStatus::Up,
            in_array($response->status(), [401, 403], true) => MediaServiceStatus::Blocked,
            default => MediaServiceStatus::Down,
        };
    }

    /**
     * What the dashboard needs to show the status dot. Never contains the status headers.
     *
     * @return array{mode: 'browser', url: string}|array{mode: 'server', state: string}|null
     */
    public function forDashboard(MediaService $service): ?array
    {
        $url = $service->statusCheckUrl();

        if ($url === null) {
            return null;
        }

        if ($service->statusCheckRunsOnServer()) {
            return ['mode' => 'server', 'state' => $this->status($service)->value];
        }

        return ['mode' => 'browser', 'url' => $url];
    }
}
