<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareIngressRule extends Model
{
    protected $fillable = [
        'cloudflare_tunnel_id',
        'hostname',
        'path',
        'service',
        'is_catch_all',
        'origin_request',
        'protocol',
        'dest_host',
        'port',
    ];

    protected $casts = [
        'is_catch_all' => 'boolean',
        'origin_request' => 'array',
    ];

    public function tunnel(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CloudflareTunnel::class, 'cloudflare_tunnel_id');
    }

    public function getProtocolAttribute(): ?string
    {
        if (! $this->service) {
            return null;
        }
        $parsed = parse_url($this->service);

        return $parsed['scheme'] ?? null;
    }

    public function setProtocolAttribute($value): void
    {
        $this->updateService($value, 'scheme');
    }

    public function getDestHostAttribute(): ?string
    {
        if (! $this->service) {
            return null;
        }
        $parsed = parse_url($this->service);

        return $parsed['host'] ?? null;
    }

    public function setDestHostAttribute($value): void
    {
        $this->updateService($value, 'host');
    }

    public function getPortAttribute(): ?string
    {
        if (! $this->service) {
            return null;
        }
        $parsed = parse_url($this->service);

        return $parsed['port'] ?? null;
    }

    public function setPortAttribute($value): void
    {
        $this->updateService($value, 'port');
    }

    protected function updateService($value, $part): void
    {
        $parsed = ($this->service ? parse_url($this->service) : []) ?: [];
        $parsed[$part] = $value;

        $scheme = $parsed['scheme'] ?? 'http';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':'.$parsed['port'] : '';
        $path = $parsed['path'] ?? '';

        $this->attributes['service'] = "{$scheme}://{$host}{$port}{$path}";
    }
}
