<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cloudflare extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'name',
        'account_id',
        'api_token',
        'status',
    ];

    protected $casts = [
        'api_token' => 'encrypted',
        'status' => \App\Enums\CloudflareStatus::class,
    ];

    public function tunnels(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CloudflareTunnel::class);
    }

    public function domains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CloudflareDomain::class);
    }

    public function ingressRules(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CloudflareIngressRule::class, CloudflareTunnel::class);
    }

    public function dnsRecords(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CloudflareDnsRecord::class, CloudflareDomain::class);
    }

    public function accessTokens(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(CloudflareAccess::class, CloudflareDomain::class);
    }
}
