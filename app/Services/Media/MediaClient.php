<?php

namespace App\Services\Media;

use App\Exceptions\MediaFetchFailed;
use App\Models\MediaService;
use Closure;
use Illuminate\Http\Client\HttpClientException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Server-side API client for one media app. Every data call goes through
 * a client, so API keys and passwords never reach the browser.
 */
abstract class MediaClient
{
    protected string $baseUrl;

    public function __construct(protected MediaService $service)
    {
        $this->baseUrl = $service->baseUrl();
    }

    /**
     * Whether the API answers with the stored credentials. Backs the Filament "Test connection" action.
     */
    abstract public function checkConnection(): bool;

    /**
     * Headers that authenticate API calls.
     *
     * @return array<string, string>
     */
    protected function authHeaders(): array
    {
        return ['X-Api-Key' => (string) $this->service->api_key];
    }

    /**
     * API request that throws on connection errors and non-2xx responses, so a
     * failed fetch is never mistaken for (and cached as) empty data.
     */
    protected function request(): PendingRequest
    {
        return Http::connectTimeout(3)->timeout(10)->withHeaders($this->authHeaders())->throw();
    }

    /**
     * Like Cache::flexible, but a failed fetch is never cached: the last good
     * value is kept and returned, or null when there is none yet, so the next
     * load tries again instead of showing an empty widget for a day.
     *
     * @param  array{0: int, 1: int}  $ttl  seconds fresh, seconds stale
     */
    protected function remember(string $key, array $ttl, Closure $fetch): ?array
    {
        try {
            return Cache::flexible($key, $ttl, $fetch);
        } catch (MediaFetchFailed $e) {
            report($e);

            return Cache::get($key);
        }
    }

    protected function respondsSuccessfully(string $path): bool
    {
        try {
            return $this->request()->get($this->baseUrl.$path)->successful();
        } catch (HttpClientException) {
            return false;
        }
    }

    protected function formatSpaceRatio($used, $free)
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

    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision).' '.$units[$pow];
    }

    protected function formatSpeed(int $bytesPerSecond): string
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
