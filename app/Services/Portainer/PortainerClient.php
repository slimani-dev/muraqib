<?php

namespace App\Services\Portainer;

use Illuminate\Support\Facades\Http;
use App\Settings\InfrastructureSettings;

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

    protected function request(): \Illuminate\Http\Client\PendingRequest
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept' => 'application/json',
        ])->baseUrl($this->baseUrl)
          ->timeout(5); 
    }

    public function getEndpoints()
    {
        // Type 1 = Docker, Type 2 = Agent
        // We might want to filter or just return all and let frontend decide?
        // Spec says: Return Collection of [id, name, publicURL]
        
        /** @var \Illuminate\Http\Client\Response $response */
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

    public function getContainers(int $endpointId)
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->request()->get("/api/endpoints/{$endpointId}/docker/containers/json", [
            'all' => 1,
        ]);

        $response->throw();

        return $response->json();
    }
    
    public function getStacks()
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->request()->get('/api/stacks');

        $response->throw();

        return $response->json();
    }

    public function restartContainer(int $endpointId, string $containerId)
    {
        /** @var \Illuminate\Http\Client\Response $response */
        $response = $this->request()->post("/api/endpoints/{$endpointId}/docker/containers/{$containerId}/restart");
        
        $response->throw();
        
        return $response->successful();
    }
}
