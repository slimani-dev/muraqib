<?php

namespace App\Services\Git;

use Illuminate\Http\Client\HttpClientException;
use Illuminate\Support\Facades\Http;

/**
 * Download counts from Packagist and npm. Missing packages or network errors give null.
 */
class PackageDownloads
{
    /**
     * @return array{total: int, monthly: int}|null
     */
    public function packagist(string $package): ?array
    {
        try {
            $downloads = Http::timeout(10)->throw()
                ->get('https://packagist.org/packages/'.$package.'/stats.json')
                ->json('downloads');
        } catch (HttpClientException) {
            return null;
        }

        return is_array($downloads) ? ['total' => (int) ($downloads['total'] ?? 0), 'monthly' => (int) ($downloads['monthly'] ?? 0)] : null;
    }

    /**
     * @return array{monthly: int}|null
     */
    public function npm(string $package): ?array
    {
        try {
            $downloads = Http::timeout(10)->throw()
                ->get('https://api.npmjs.org/downloads/point/last-month/'.$package)
                ->json('downloads');
        } catch (HttpClientException) {
            return null;
        }

        return is_numeric($downloads) ? ['monthly' => (int) $downloads] : null;
    }
}
