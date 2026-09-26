<?php

namespace App\Console\Commands;

use App\Models\CloudflareIngressRule;
use App\Models\CloudflareTunnel;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Console\Command;

class MoveIngressRuleToEnd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cloudflare:move-ingress-rule-to-end
        {hostname : The public hostname of the rule to move, e.g. skipex.slimani.dev}
        {--path= : Path matcher of the rule to move (omit to target its catch-all/no-path rule)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-create an ingress rule so it sorts after its siblings on the same hostname, since cloudflared matches rules top-to-bottom and this table has no explicit order column';

    public function handle(CloudflareService $service): int
    {
        $hostname = $this->argument('hostname');
        $path = $this->option('path');

        $rule = CloudflareIngressRule::where('hostname', $hostname)
            ->where('path', $path)
            ->first();

        if (! $rule) {
            $this->error("No ingress rule found for [{$hostname}] with path [{$path}].");

            return self::FAILURE;
        }

        $tunnel = CloudflareTunnel::findOrFail($rule->cloudflare_tunnel_id);

        $attributes = $rule->only([
            'cloudflare_tunnel_id',
            'hostname',
            'path',
            'service',
            'is_catch_all',
            'origin_request',
        ]);

        $rule->delete();
        CloudflareIngressRule::create($attributes);

        $this->info("Moved ingress rule for [{$hostname}] (path: ".($path ?: '<none>').") to the end of tunnel [{$tunnel->name}].");

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
}
