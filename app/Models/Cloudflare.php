<?php

namespace App\Models;

use App\Enums\CloudflareStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Cloudflare extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'account_id',
        'api_token',
        'status',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'status' => CloudflareStatus::class,
    ];

    public function tunnels(): HasMany
    {
        return $this->hasMany(CloudflareTunnel::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(CloudflareDomain::class);
    }

    public function ingressRules(): HasManyThrough
    {
        return $this->hasManyThrough(CloudflareIngressRule::class, CloudflareTunnel::class);
    }

    public function dnsRecords(): HasManyThrough
    {
        return $this->hasManyThrough(CloudflareDnsRecord::class, CloudflareDomain::class);
    }

    public function serviceTokens(): HasMany
    {
        return $this->hasMany(CloudflareServiceToken::class, 'account_id');
    }

    public function accessApplications(): HasMany
    {
        return $this->hasMany(CloudflareAccessApplication::class, 'account_id');
    }
}
