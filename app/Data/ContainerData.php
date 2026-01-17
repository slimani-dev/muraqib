<?php

namespace App\Data;

class ContainerData
{
    public function __construct(
        public string $id,
        public string $name,
        public string $image,
        public string $status,
        public string $state,
        public ?string $stackName = null,
        public ?array $ports = null,
        public ?array $labels = null,
        public ?string $createdAt = null,
        public ?array $raw = null,
        public ?int $endpointId = null,
        public ?string $endpointName = null,
    ) {}

    public static function fromApi(array $data, ?int $endpointId = null, ?string $endpointName = null): self
    {
        $name = isset($data['Names'][0]) ? ltrim($data['Names'][0], '/') : ($data['Id'] ?? 'Unknown');

        // Extract stack name from labels
        $stackName = null;
        $labels = $data['Labels'] ?? [];
        if (isset($labels['com.docker.compose.project'])) {
            $stackName = $labels['com.docker.compose.project'];
        }

        return new self(
            id: $data['Id'] ?? 'unknown',
            name: $name,
            image: $data['Image'] ?? 'unknown',
            status: $data['Status'] ?? 'unknown',
            state: $data['State'] ?? 'unknown',
            stackName: $stackName,
            ports: $data['Ports'] ?? null,
            labels: $labels,
            createdAt: isset($data['Created']) ? date('Y-m-d H:i:s', $data['Created']) : null,
            raw: $data,
            endpointId: $endpointId,
            endpointName: $endpointName,
        );
    }
}
