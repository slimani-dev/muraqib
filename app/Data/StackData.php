<?php

namespace App\Data;

class StackData
{
    public function __construct(
        public int $id,
        public string $name,
        public int $endpointId,
        public string $status,
        public ?string $type = null,
        public ?array $env = null,
        public ?string $createdAt = null,
        public ?string $updatedAt = null,
        public ?array $raw = null,
    ) {}

    public static function fromApi(array $data): self
    {
        return new self(
            id: $data['Id'] ?? 0,
            name: $data['Name'] ?? 'Unknown',
            endpointId: $data['EndpointId'] ?? 0,
            status: isset($data['Status']) ? (string) $data['Status'] : 'unknown',
            type: $data['Type'] ?? null,
            env: $data['Env'] ?? null,
            createdAt: isset($data['CreationDate']) ? date('Y-m-d H:i:s', $data['CreationDate']) : null,
            updatedAt: isset($data['UpdateDate']) ? date('Y-m-d H:i:s', $data['UpdateDate']) : null,
            raw: $data,
        );
    }
}
