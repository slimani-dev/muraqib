<?php

namespace App\Console\Commands;

use App\Models\CloudflareDomain;
use App\Models\CloudflareTunnel;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Console\Command;

class CreateIngressRule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:create-ingress-rule
        {hostname : The public hostname, e.g. app.example.com}
        {service : The origin service URL, e.g. http://192.168.1.2:8083}
        {--tunnel= : Tunnel id or name (defaults to the tunnel already serving the hostname\'s domain, or the only tunnel on that domain\'s account)}
        {--path= : Path matcher for this rule (omit for a catch-all rule on this hostname)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add an ingress rule to an existing Cloudflare Tunnel, create its DNS CNAME, and push the updated config to Cloudflare';

    public function handle(CloudflareService $service): int
    {
        $hostname = $this->argument('hostname');
        $serviceUrl = $this->argument('service');
        $path = $this->option('path');

        $domain = CloudflareDomain::all()
            ->first(fn (CloudflareDomain $domain): bool => str($hostname)->endsWith('.'.$domain->name) || $hostname === $domain->name);

        if (! $domain) {
            $this->error("No Cloudflare domain configured that matches [{$hostname}].");

            return self::FAILURE;
        }

        $tunnel = $this->resolveTunnel($domain);

        if (! $tunnel) {
            return self::FAILURE;
        }

        $existing = $tunnel->ingressRules()
            ->where('hostname', $hostname)
            ->where('path', $path)
            ->first();

        if ($existing) {
            $existing->update(['service' => $serviceUrl]);
            $this->info("Updated existing ingress rule for [{$hostname}] on tunnel [{$tunnel->name}].");
        } else {
            $tunnel->ingressRules()->create([
                'hostname' => $hostname,
                'service' => $serviceUrl,
                'path' => $path,
                'is_catch_all' => false,
            ]);
            $this->info("Created ingress rule for [{$hostname}] -> {$serviceUrl} on tunnel [{$tunnel->name}].");
        }

        $this->comment('Ensuring DNS CNAME record...');

        try {
            $result = $service->ensureCnameRecord($domain, $hostname, "{$tunnel->tunnel_id}.cfargotunnel.com");
            $this->info("DNS CNAME: {$result}.");
        } catch (\Exception $e) {
            $this->warn('DNS CNAME could not be ensured: '.$e->getMessage());
        }

        $this->comment('Pushing ingress configuration to Cloudflare...');

        try {
            $service->updateIngressRules($tunnel);
            $this->info('Ingress configuration pushed successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to push ingress configuration: '.$e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    protected function resolveTunnel(CloudflareDomain $domain): ?CloudflareTunnel
    {
        $tunnelOption = $this->option('tunnel');

        if ($tunnelOption) {
            $tunnel = CloudflareTunnel::where('cloudflare_id', $domain->cloudflare_id)
                ->where(fn ($query) => $query->where('id', $tunnelOption)->orWhere('name', $tunnelOption))
                ->first();

            if (! $tunnel) {
                $this->error("Tunnel [{$tunnelOption}] not found on this domain's Cloudflare account.");

                return null;
            }

            return $tunnel;
        }

        $tunnels = CloudflareTunnel::where('cloudflare_id', $domain->cloudflare_id)->get();

        if ($tunnels->count() === 1) {
            return $tunnels->first();
        }

        $this->error("Multiple tunnels exist on this domain's Cloudflare account — pass --tunnel= to pick one: "
            .$tunnels->pluck('name')->implode(', '));

        return null;
    }
}
