<?php

namespace App\Services\Portainer;

use App\Settings\InfrastructureSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class PortainerClient
{
    protected ?string $baseUrl;

    protected ?string $apiKey;

    public function __construct(InfrastructureSettings $settings)
    {
        $this->baseUrl = $settings->portainer_url;
        $this->apiKey = $settings->portainer_api_key;
    }

    public function withCredentials(string $url, string $key): self
    {
        $this->baseUrl = $url;
        $this->apiKey = $key;

        return $this;
    }

    protected function request(): PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)
            ->timeout(5);
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getEndpoints(): Collection
    {
        // Type 1 = Docker, Type 2 = Agent
        // We might want to filter or just return all and let frontend decide?
        // Spec says: Return Collection of [id, name, publicURL]

        /** @var Response $response */
        $response = $this->request()->get('/api/endpoints', [
            // 'types' => [1, 2] // Does Portainer support filtering by types in array?
            // Often it's client side filtering or specific query params.
            // Let's get all for now.
        ]);

        $response->throw();

        return collect($response->json())->map(function ($endpoint) {
            return [
                'id' => $endpoint['Id'],
                'name' => $endpoint['Name'],
                'url' => $endpoint['PublicURL'] ?? $endpoint['URL'] ?? '',
                'type' => $endpoint['Type'],
            ];
        });
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getContainers(int $endpointId)
    {
        /** @var Response $response */
        $response = $this->request()->get("/api/endpoints/{$endpointId}/docker/containers/json", [
            'all' => 1,
        ]);

        $response->throw();

        return $response->json();
    }

    public function getStacks()
    {
        /** @var Response $response */
        $response = $this->request()->get('/api/stacks');

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function getStack(int $stackId)
    {
        /** @var Response $response */
        $response = $this->request()->get("/api/stacks/{$stackId}");

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function startStack(int $stackId, int $endpointId)
    {
        /** @var Response $response */
        $response = $this->request()->post("/api/stacks/{$stackId}/start?endpointId={$endpointId}");

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function stopStack(int $stackId, int $endpointId)
    {
        /** @var Response $response */
        $response = $this->request()->post("/api/stacks/{$stackId}/stop?endpointId={$endpointId}");

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function updateStack(int $stackId, int $endpointId, array $data)
    {
        /** @var Response $response */
        $response = $this->request()->put("/api/stacks/{$stackId}?endpointId={$endpointId}", $data);

        $response->throw();

        return $response->json();
    }

    /**
     * @throws RequestException
     * @throws ConnectionException
     */
    public function restartContainer(int $endpointId, string $containerId): bool
    {
        /** @var Response $response */
        $response = $this->request()->post("/api/endpoints/{$endpointId}/docker/containers/{$containerId}/restart");

        $response->throw();

        return $response->successful();
    }
}
