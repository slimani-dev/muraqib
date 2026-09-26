<?php

namespace App\Console\Commands;

use App\Models\CloudflareDomain;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Console\Command;

class PurgeCloudflareUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:purge-urls
        {urls* : One or more full URLs to purge from the edge cache}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Purge specific URLs from Cloudflare\'s edge cache (e.g. a stale cached error response)';

    public function handle(CloudflareService $service): int
    {
        $urls = $this->argument('urls');

        foreach (collect($urls)->groupBy(fn (string $url) => parse_url($url, PHP_URL_HOST)) as $host => $hostUrls) {
            $domain = CloudflareDomain::all()
                ->first(fn (CloudflareDomain $domain): bool => str($host)->endsWith('.'.$domain->name) || $host === $domain->name);

            if (! $domain) {
                $this->error("No Cloudflare domain configured that matches [{$host}].");

                return self::FAILURE;
            }

            try {
                $service->purgeUrls($domain, $hostUrls->values()->all());
                $this->info('Purged '.$hostUrls->count()." URL(s) on [{$domain->name}].");
            } catch (\Exception $e) {
                $this->error('Failed to purge cache: '.$e->getMessage());

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
