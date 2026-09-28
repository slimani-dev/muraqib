<?php

namespace App\Http\Controllers\Api;

use App\Enums\UpdateStatus;
use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Services\RegistryApiService;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ContainerApiController extends Controller
{
    public function ping(Request $request)
    {
        $containers = Container::whereNotNull('url')->get();

        if ($containers->isEmpty()) {
            return response()->json([]);
        }

        $urls = $containers->pluck('url', 'container_id')->toArray();

        // Shared for 30 seconds, so several open tabs or quick reloads don't each ping every container
        $results = Cache::remember('containers_ping_'.md5(json_encode($urls)), 30, function () use ($urls): array {
            $responses = Http::pool(function (Pool $pool) use ($urls) {
                foreach ($urls as $id => $url) {
                    // Short timeouts, no SSL verification for local IPs
                    $pool->as($id)->connectTimeout(2)->timeout(3)->withoutVerifying()->get($url);
                }
            });

            $results = [];
            foreach ($responses as $id => $response) {
                // Reachable if we get ANY HTTP response back (even 401, 500, etc)
                $results[$id] = $response instanceof Response;
            }

            return $results;
        });

        return response()->json($results);
    }

    public function checkUpdate(Container $container, RegistryApiService $registryApi)
    {
        if (empty($container->image_digest)) {
            return back()->with('error', 'Container has no image digest');
        }

        $result = $registryApi->checkDigest($container->image, $container->image_digest);

        if (! $result || isset($result['error']) || ! isset($result['is_latest'])) {
            $container->update([
                'update_status' => UpdateStatus::Error,
                'update_checked_at' => now(),
                'update_error' => $result['error'] ?? 'Could not reach registry API',
            ]);

            return back()->with('error', $result['error'] ?? 'Could not reach registry API');
        }

        if ($result['is_latest']) {
            $container->update([
                'update_status' => UpdateStatus::UpToDate,
                'update_checked_at' => now(),
                'current_release' => $result['current_tag'] ?? null,
                'available_tags' => $result['relevant_tags'] ?? null,
                'latest_digest' => $result['latest_digest'] ?? null,
                'update_error' => null,
            ]);

            return back()->with('success', 'Image is Up to Date');
        } else {
            $container->update([
                'update_status' => UpdateStatus::UpdateAvailable,
                'update_checked_at' => now(),
                'current_release' => $result['current_tag'] ?? null,
                'available_tags' => $result['relevant_tags'] ?? null,
                'latest_digest' => $result['latest_digest'] ?? null,
                'update_error' => null,
            ]);

            return back()->with('success', 'Update Available');
        }
    }
}
