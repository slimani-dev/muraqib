<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Portainer;
use App\Services\Portainer\PortainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PortainerStatusController extends Controller
{
    public function check(): JsonResponse
    {
        try {
            $portainer = Portainer::first();

            if (! $portainer) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No Portainer configuration found.',
                ], 404);
            }

            $client = new PortainerService($portainer);
            $start = microtime(true);
            $client->getEndpoints();
            $duration = round((microtime(true) - $start) * 1000);

            return response()->json([
                'status' => 'connected',
                'latency_ms' => $duration,
            ]);
        } catch (\Exception $e) {
            Log::error('Portainer status check failed: '.$e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function containers(): JsonResponse
    {
        try {
            $portainer = Portainer::first();

            if (! $portainer) {
                return response()->json([
                    'total' => 0,
                    'running' => 0,
                    'stopped' => 0,
                    'other' => 0,
                ]);
            }

            $client = new PortainerService($portainer);

            // Get endpoints to find the primary one
            $endpoints = $client->getEndpoints();

            if ($endpoints->isEmpty()) {
                return response()->json([
                    'total' => 0,
                    'running' => 0,
                    'stopped' => 0,
                    'other' => 0,
                ]);
            }

            // Use the first endpoint
            $firstEndpoint = $endpoints->first();
            $containers = collect($client->getContainers($firstEndpoint['id']));

            $running = $containers->where('State', 'running')->count();
            $exited = $containers->where('State', 'exited')->count();
            $total = $containers->count();
            $other = $total - $running - $exited;

            return response()->json([
                'total' => $total,
                'running' => $running,
                'stopped' => $exited,
                'other' => $other,
            ]);

        } catch (\Exception $e) {
            Log::error('Portainer container stats failed: '.$e->getMessage());

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function stacks(): JsonResponse
    {
        try {
            $portainer = Portainer::first();

            if (! $portainer) {
                return response()->json([
                    'total' => 0,
                    'active' => 0,
                    'inactive' => 0,
                ]);
            }

            $client = new PortainerService($portainer);
            $stacks = collect($client->getStacks());

            // Status: 1 = Active, 2 = Inactive
            $active = $stacks->where('Status', 1)->count();
            $inactive = $stacks->where('Status', 2)->count();
            $total = $stacks->count();

            // If statuses are different, 'active' might capture standard active ones.
            // Portainer Stacks usually are 1 (Active) or 2 (Inactive).
            // Some versions might differ, but this is standard.

            return response()->json([
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
            ]);

        } catch (\Exception $e) {
            Log::error('Portainer stacks fetch failed: '.$e->getMessage());

            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
