<?php

namespace App\Services;

use App\Data\ContainerData;
use App\Data\StackData;
use App\Models\Portainer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PortainerService
{
    public function __construct(public Portainer $portainer) {}

    public function checkConnection(): bool
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->timeout(5)
                ->get("{$this->portainer->url}/api/system/status");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getSystemInfo(): ?array
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/system/status");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // log error
        }

        return null;
    }

    public function getVersion(): ?string
    {
        $info = $this->getSystemInfo();

        return $info['Version'] ?? null;
    }

    public function getUptime(): ?int
    {
        // Portainer doesn't provide uptime directly in the API
        // We can calculate it from the container running time if needed
        return null;
    }

    public function getEndpoints(): array
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/endpoints");

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            // log error
        }

        return [];
    }

    /**
     * Get all stacks across all endpoints
     */
    public function getStacks(): Collection
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/stacks");

            if ($response->successful()) {
                $stacks = $response->json();

                return collect($stacks)->map(fn ($stack) => StackData::fromApi($stack));
            }
        } catch (\Exception $e) {
            // log error
        }

        return collect();
    }

    /**
     * Get a single stack by ID
     */
    public function getStack(int $stackId): ?StackData
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/stacks/{$stackId}");

            if ($response->successful()) {
                return StackData::fromApi($response->json());
            }
        } catch (\Exception $e) {
            // log error
        }

        return null;
    }

    /**
     * Get stack file content (docker-compose.yml)
     */
    public function getStackFile(int $stackId): ?string
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/stacks/{$stackId}/file");

            if ($response->successful()) {
                return $response->json()['StackFileContent'] ?? null;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PortainerService::getStackFile failed for stack $stackId: ".$e->getMessage());
        }

        return null;
    }

    /**
     * Get all containers for a specific endpoint
     */
    public function getContainers(?int $endpointId = null): Collection
    {
        // If no endpoint specified, try to get the first one
        if ($endpointId === null) {
            $endpoints = $this->getEndpoints();
            if (empty($endpoints)) {
                return collect();
            }
            $endpointId = $endpoints[0]['Id'] ?? 1;
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/endpoints/{$endpointId}/docker/containers/json?all=1");

            if ($response->successful()) {
                $containers = $response->json();

                return collect($containers)->map(fn ($container) => ContainerData::fromApi($container, $endpointId));
            }
        } catch (\Exception $e) {
            // log error
        }

        return collect();
    }

    /**
     * Get containers for a specific stack
     */
    public function getStackContainers(string $stackName): Collection
    {
        $allContainers = $this->getContainers();

        return $allContainers->filter(fn (ContainerData $container) => $container->stackName === $stackName);
    }

    /**
     * Get a single container by ID
     */
    public function getContainer(string $containerId, ?int $endpointId = null): ?ContainerData
    {
        if ($endpointId === null) {
            $endpoints = $this->getEndpoints();
            if (empty($endpoints)) {
                return null;
            }
            $endpointId = $endpoints[0]['Id'] ?? 1;
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->get("{$this->portainer->url}/api/endpoints/{$endpointId}/docker/containers/{$containerId}/json");

            if ($response->successful()) {
                return ContainerData::fromApi($response->json());
            }
        } catch (\Exception $e) {
            // log error
        }

        return null;
    }

    /**
     * Perform full sync (stacks + system info + updates)
     */
    public function sync(): array
    {
        $results = [
            'connected' => false,
            'update_info' => null,
        ];

        // 1. Sync System Info (Version, Uptime, etc)
        $info = $this->getSystemInfo();

        if ($info) {
            $results['connected'] = true;
            // Update initial info
            $this->portainer->update([
                'version' => $info['Version'] ?? null,
                'uptime' => null, // Not available in API
                'last_synced_at' => now(),
                'data' => array_merge($this->portainer->data ?? [], ['info' => $info]),
            ]);
        } else {
            return $results; // Stop if no connection
        }

        // 2. Check for updates
        $results['update_info'] = $this->checkForUpdates();

        // 3. Sync Stacks
        $this->syncStacks(true);

        // 4. Sync Containers
        $this->syncContainers(true);

        // 5. Cache Stats
        // We fetch endpoints again here to get the count. 
        // This is an extra request but necessary since we don't store endpoints locally.
        $endpoints = $this->getEndpoints();
        
        $stats = [
            'endpoints_count' => count($endpoints),
            // We can also cache DB counts if we really want to avoid even those DB queries, 
            // but Model::count() is usually fast enough. 
            // Let's cache them for consistency so the infolist is purely reading from the cached JSON if desired,
            // but the plan was to use relations for these.
            // I'll stick to just endpoints_count here as per plan, but adding others wouldn't hurt.
        ];

        $data = $this->portainer->data ?? [];
        $data['stats'] = $stats;

        // Perform a final quiet update to save the stats
        $this->portainer->updateQuietly([
            'data' => $data,
        ]);

        return $results;
    }

    /**
     * Check for updates from GitHub
     */
    public function checkForUpdates(): ?array
    {
        try {
            $response = Http::get('https://api.github.com/repos/portainer/portainer/releases/latest');

            if ($response->successful()) {
                $latestRelease = $response->json();
                $latestVersion = ltrim($latestRelease['tag_name'] ?? '', 'v');
                $currentVersion = ltrim($this->getVersion() ?? '', 'v');

                // Update model
                $this->portainer->update([
                    'latest_version' => $latestVersion,
                ]);

                return [
                    'current' => $currentVersion,
                    'latest' => $latestVersion,
                    'update_available' => version_compare($latestVersion, $currentVersion, '>'),
                    'release_url' => $latestRelease['html_url'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to check for updates: '.$e->getMessage());
        }

        return null;
    }

    /**
     * Get summary statistics
     */
    public function getStats(): array
    {
        $endpoints = $this->getEndpoints();
        $stacks = $this->getStacks();
        $containers = $this->getContainers();

        return [
            'endpoints_count' => count($endpoints),
            'stacks_count' => $stacks->count(),
            'containers_count' => $containers->count(),
            'containers_running' => $containers->filter(fn (ContainerData $c) => $c->state === 'running')->count(),
        ];
    }

    /**
     * Start/Deploy a stack
     */
    public function startStack(int $stackId, int $endpointId): bool
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->post("{$this->portainer->url}/api/stacks/{$stackId}/start?endpointId={$endpointId}");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Stop a stack
     */
    public function stopStack(int $stackId, int $endpointId): bool
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->post("{$this->portainer->url}/api/stacks/{$stackId}/stop?endpointId={$endpointId}");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Restart a stack (stop then start)
     */
    public function restartStack(int $stackId, int $endpointId): bool
    {
        $stopped = $this->stopStack($stackId, $endpointId);
        if (! $stopped) {
            return false;
        }

        sleep(2); // Wait for stack to stop

        return $this->startStack($stackId, $endpointId);
    }

    /**
     * Delete a stack
     */
    public function deleteStack(int $stackId, int $endpointId): bool
    {
        try {
            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->delete("{$this->portainer->url}/api/stacks/{$stackId}?endpointId={$endpointId}");

            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Sync stacks from API with detailed logging
     */
    public function syncStacks(bool $force = false): void
    {
        \Illuminate\Support\Facades\Log::info("Starting syncStacks for Portainer: {$this->portainer->name} (ID: {$this->portainer->id}) - Force: ".($force ? 'Yes' : 'No'));

        // Redis cache key for this portainer's last sync
        $cacheKey = "portainer:{$this->portainer->id}:stacks:last_sync";
        $cacheDuration = 300; // 5 minutes in seconds

        // Check if we need to sync (unless forced)
        if (! $force) {
            $lastSync = cache()->get($cacheKey);
            if ($lastSync && now()->diffInSeconds($lastSync) < $cacheDuration) {
                \Illuminate\Support\Facades\Log::info('  - Cache valid. Skipping sync.');

                return;
            }
        }

        \Illuminate\Support\Facades\Log::info('  - Fetching stacks from API...');
        $apiStacks = $this->getStacks();
        \Illuminate\Support\Facades\Log::info('  - Fetched '.$apiStacks->count().' stacks.');

        $apiContainers = $this->getContainers();
        \Illuminate\Support\Facades\Log::info('  - Fetched '.$apiContainers->count().' containers.');

        // Delete stacks that no longer exist in API
        $apiStackIds = $apiStacks->pluck('id')->toArray();
        \App\Models\Stack::where('portainer_id', $this->portainer->id)
            ->whereNotIn('external_id', $apiStackIds)
            ->delete();

        // Upsert stacks from API
        foreach ($apiStacks as $stackData) {
            $icon = null;
            \Illuminate\Support\Facades\Log::info('  --------------------------------------------------');
            \Illuminate\Support\Facades\Log::info("  - Processing Stack: {$stackData->name} (ID: {$stackData->id}, Status: {$stackData->status})");

            // Fetch Stack File Content (Docker Compose)
            $stackFileContent = $this->getStackFile((int) $stackData->id);
            if ($stackFileContent) {
                \Illuminate\Support\Facades\Log::info('    - Stack file content fetched.');
            }

            // Fetch Detailed Stack Info (for Env)
            $fullStackData = $this->getStack((int) $stackData->id);
            $env = [];
            if ($fullStackData && $fullStackData->env) {
                 foreach ($fullStackData->env as $envVar) {
                    if (is_array($envVar) && isset($envVar['name'], $envVar['value'])) {
                        $env[] = $envVar;
                    } elseif (is_string($envVar) && str_contains($envVar, '=')) {
                        [$key, $val] = explode('=', $envVar, 2);
                        $env[] = ['name' => $key, 'value' => $val];
                    }
                }
                \Illuminate\Support\Facades\Log::info('    - Environment variables fetched: ' . count($env));
            }

            // Icon detection logic (simplified/preserved)
            if (!$icon) {
                 // Try to parse from stack file if available
                 if ($stackFileContent) {
                      try {
                          $compose = \Symfony\Component\Yaml\Yaml::parse($stackFileContent);
                          // ... implementation of icon finding from compose ...
                           if (isset($compose['services']) && is_array($compose['services'])) {
                                foreach ($compose['services'] as $serviceName => $serviceData) {
                                    if (isset($serviceData['labels'])) {
                                        $labels = $this->normalizeLabels($serviceData['labels']);
                                        // Check main
                                        if (isset($labels['muraqib.main']) && $labels['muraqib.main'] === 'true') {
                                            $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
                                            if ($icon) break;
                                        }
                                        // Fallback
                                        if (!$icon) {
                                             $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
                                        }
                                    }
                                }
                           }
                      } catch (\Exception $e) { /* ignore */ }
                 }
                 // If still no icon and running, try containers (existing logic)
                 if (!$icon && (string) $stackData->status === '1') { // 1 = running
                      // ... existing container logic ...
                      $stackContainers = $apiContainers->filter(function ($container) use ($stackData) {
                          return ($container->labels['com.docker.compose.project'] ?? '') === $stackData->name;
                      });

                      // Try main container
                      $mainContainer = $stackContainers->first(function ($container) {
                          return ($container->labels['muraqib.main'] ?? 'false') === 'true';
                      });
                      if ($mainContainer) {
                          $icon = $mainContainer->labels['muraqib.icon'] ?? $mainContainer->labels['glance.icon'] ?? null;
                      }

                      // Fallback container
                      if (!$icon) {
                           $containerWithIcon = $stackContainers->first(function ($container) {
                                return isset($container->labels['muraqib.icon']) || isset($container->labels['glance.icon']);
                            });
                            if ($containerWithIcon) {
                                $icon = $containerWithIcon->labels['muraqib.icon'] ?? $containerWithIcon->labels['glance.icon'] ?? null;
                            }
                      }
                 }
            }


            \App\Models\Stack::updateOrCreate(
                [
                    'portainer_id' => $this->portainer->id,
                    'external_id' => (string) $stackData->id,
                ],
                [
                    'name' => $stackData->name,
                    'endpoint_id' => $stackData->endpointId,
                    'stack_status' => $stackData->status,
                    'stack_type' => $stackData->type,
                    'icon' => $icon,
                    'created_at_portainer' => $stackData->createdAt,
                    'stack_file_content' => $stackFileContent,
                    'env' => $env,
                ]
            );
        }

        // Update cache timestamp
        cache()->put($cacheKey, now(), $cacheDuration * 2);
        \Illuminate\Support\Facades\Log::info('Sync Complete.');
    }

    /**
     * Normalize labels from docker-compose (can be array of strings or object)
     */
    protected function normalizeLabels(array $labels): array
    {
        $normalized = [];

        // Check if associative array (map) or indexed array (list)
        $isList = array_key_exists(0, $labels);

        if ($isList) {
            foreach ($labels as $label) {
                if (is_string($label) && str_contains($label, '=')) {
                    [$key, $value] = explode('=', $label, 2);
                    $normalized[trim($key)] = trim($value);
                }
            }
        } else {
            $normalized = $labels;
        }

        return $normalized;
    }

    /**
     * Sync containers from API with detailed logging
     */
    /**
     * Sync containers from API with detailed logging (All Endpoints)
     */
    public function syncContainers(bool $force = false): void
    {
        \Illuminate\Support\Facades\Log::info("Starting syncContainers for Portainer: {$this->portainer->name}");

        // Redis cache key for this portainer's last sync
        $cacheKey = "portainer:{$this->portainer->id}:containers:last_sync";
        $cacheDuration = 300; // 5 minutes in seconds

        // Check if we need to sync (unless forced)
        if (! $force) {
            $lastSync = cache()->get($cacheKey);
            if ($lastSync && now()->diffInSeconds($lastSync) < $cacheDuration) {
                \Illuminate\Support\Facades\Log::info('  - Cache valid. Skipping sync.');

                return;
            }
        }

        $endpoints = $this->getEndpoints();
        $allApiContainers = collect();

        foreach ($endpoints as $endpoint) {
            $endpointId = $endpoint['Id'];
            $endpointName = $endpoint['Name'];
            \Illuminate\Support\Facades\Log::info("  - Fetching containers for Endpoint: $endpointName ($endpointId)");

            $containers = $this->getContainers($endpointId);
            // Manually attach endpoint info
            $containers->each(function (ContainerData $c) use ($endpointId, $endpointName) {
                $c->endpointId = $endpointId;
                $c->endpointName = $endpointName;
            });

            \Illuminate\Support\Facades\Log::info("    - Found {$containers->count()} containers.");
            $allApiContainers = $allApiContainers->merge($containers);
        }

        \Illuminate\Support\Facades\Log::info('  - Total containers fetched: '.$allApiContainers->count());

        // Delete containers that no longer exist in API (across all endpoints)
        $apiContainerIds = $allApiContainers->pluck('id')->toArray();
        \App\Models\Container::where('portainer_id', $this->portainer->id)
            ->whereNotIn('container_id', $apiContainerIds)
            ->delete();

        // Upsert containers from API
        foreach ($allApiContainers as $containerData) {
            $labels = $containerData->labels ?? [];

            // Extract metadata
            $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
            $displayName = $labels['muraqib.name'] ?? $labels['glance.name'] ?? null;
            $url = $labels['muraqib.url'] ?? $labels['glance.url'] ?? null;
            $description = $labels['muraqib.description'] ?? $labels['glance.description'] ?? null;
            $isMain = ($labels['muraqib.main'] ?? 'false') === 'true';

            \App\Models\Container::updateOrCreate(
                [
                    'portainer_id' => $this->portainer->id,
                    'container_id' => $containerData->id,
                ],
                [
                    'name' => $containerData->name,
                    'image' => $containerData->image,
                    'state' => $containerData->state,
                    'status' => $containerData->status,
                    'stack_name' => $containerData->stackName,
                    'endpoint_id' => $containerData->endpointId,
                    'endpoint_name' => $containerData->endpointName,
                    'created_at_portainer' => $containerData->createdAt,
                    'icon' => $icon,
                    'display_name' => $displayName,
                    'url' => $url,
                    'description' => $description,
                    'is_main' => $isMain,
                ]
            );
        }

        // Update cache timestamp
        cache()->put($cacheKey, now(), $cacheDuration * 2);
        \Illuminate\Support\Facades\Log::info('Container Sync Complete.');
    }

    /**
     * Update a stack (content and environment variables)
     */
    public function updateStack(int $stackId, int $endpointId, string $stackFileContent, array $env, bool $prune = false): bool
    {
        try {
            $payload = [
                'StackFileContent' => $stackFileContent,
                'Env' => $env, // Expected format: [['name' => 'KEY', 'value' => 'VAL'], ...]
                'Prune' => $prune,
            ];

            $response = Http::withHeaders(['X-API-Key' => $this->portainer->access_token])
                ->put("{$this->portainer->url}/api/stacks/{$stackId}?endpointId={$endpointId}", $payload);

            return $response->successful();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("PortainerService::updateStack failed for stack $stackId: ".$e->getMessage());

            return false;
        }
    }
}
