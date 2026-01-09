<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Portainer\PortainerClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PortainerStacksController extends Controller
{
    public function __construct(protected PortainerClient $client) {}

    public function index(): JsonResponse
    {
        try {
            $stacks = collect($this->client->getStacks());

            // Get endpoints to fetch containers
            $endpoints = $this->client->getEndpoints();
            $endpointId = $endpoints->first()['id'] ?? 1; // Default to 1 if fails, but usually 2 or dynamic

            $containers = collect($this->client->getContainers($endpointId));

            $stacksWithContainers = $stacks->map(function ($stack) use ($containers) {
                // Filter containers belonging to this stack
                // Portainer stacks usually label containers with 'com.docker.compose.project' = stack name
                $stackContainers = $containers->filter(function ($container) use ($stack) {
                    $labels = $container['Labels'] ?? [];

                    return ($labels['com.docker.compose.project'] ?? '') === $stack['Name'];
                });

                $stack['containers_count'] = $stackContainers->count();
                $stack['running_count'] = $stackContainers->where('State', 'running')->count();
                $stack['stopped_count'] = $stackContainers->where('State', '!=', 'running')->count();

                // Icon Logic
                $mainContainer = $stackContainers->first(function ($container) {
                    return ($container['Labels']['muraqib.main'] ?? 'false') === 'true';
                });

                $icon = null;

                if ($mainContainer) {
                    $icon = $mainContainer['Labels']['muraqib.icon']
                        ?? $mainContainer['Labels']['glance.icon']
                        ?? null;
                }

                if (! $icon) {
                    // Find first container with an icon
                    $containerWithIcon = $stackContainers->first(function ($container) {
                        return isset($container['Labels']['muraqib.icon']) || isset($container['Labels']['glance.icon']);
                    });

                    if ($containerWithIcon) {
                        $icon = $containerWithIcon['Labels']['muraqib.icon']
                            ?? $containerWithIcon['Labels']['glance.icon'];
                    }
                }

                $stack['icon'] = $icon;

                return $stack;
            });

            return response()->json($stacksWithContainers);
        } catch (\Exception $e) {
            Log::error('Failed to list stacks: '.$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    private function getEndpointId(): int
    {
        // For now, take the first available endpoint (usually ID 2 for Local Agent or Docker)
        // In the future this could be configurable or passed via request
        $endpoints = $this->client->getEndpoints();

        return $endpoints->first()['id'] ?? 1;
    }

    public function start(string $id): JsonResponse
    {
        try {
            return response()->json($this->client->startStack((int) $id, $this->getEndpointId()));
        } catch (\Exception $e) {
            Log::error("Failed to start stack {$id}: ".$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function stop(string $id): JsonResponse
    {
        try {
            return response()->json($this->client->stopStack((int) $id, $this->getEndpointId()));
        } catch (\Exception $e) {
            Log::error("Failed to stop stack {$id}: ".$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function restart(string $id): JsonResponse
    {
        try {
            $endpointId = $this->getEndpointId();

            // Attempt to stop then start
            try {
                $this->client->stopStack((int) $id, $endpointId);
            } catch (\Exception $e) {
                // Continue to start
            }

            sleep(2);

            return response()->json($this->client->startStack((int) $id, $endpointId));
        } catch (\Exception $e) {
            Log::error("Failed to restart stack {$id}: ".$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            // Validate request if needed, for now pass all
            return response()->json($this->client->updateStack((int) $id, $this->getEndpointId(), $request->all()));
        } catch (\Exception $e) {
            Log::error("Failed to update stack {$id}: ".$e->getMessage());

            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
