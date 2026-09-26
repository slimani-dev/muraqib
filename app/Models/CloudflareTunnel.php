<?php

namespace App\Models;

use App\Enums\CloudflareStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareTunnel extends Model
{
    protected $fillable = [
        'cloudflare_id',
        'tunnel_id',
        'name',
        'token',
        'is_active',
        'status',
        'description',
        'loglevel',
        'transport_loglevel',
        'protocol',
        'proxy_dns',
        'proxy_dns_port',
        'proxy_dns_upstream',
        'conns_active_at',
        'conns_inactive_at',
        'client_version',
        'remote_config',
        'status_checked_at',
    ];

    protected $casts = [
        'token' => 'encrypted',
        'is_active' => 'boolean',
        'proxy_dns' => 'boolean',
        'proxy_dns_upstream' => 'array',
        'remote_config' => 'boolean',
        'conns_active_at' => 'datetime',
        'conns_inactive_at' => 'datetime',
        'status_checked_at' => 'datetime',
        'status' => CloudflareStatus::class,
    ];

    public function cloudflare(): BelongsTo
    {
        return $this->belongsTo(Cloudflare::class);
    }

    public function ingressRules(): HasMany
    {
        return $this->hasMany(CloudflareIngressRule::class);
    }
}
