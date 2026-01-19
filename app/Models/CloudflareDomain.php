<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareDomain extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected $fillable = [
        'cloudflare_id',
        'zone_id',
        'name',
        'status',
    ];

    public function cloudflare(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cloudflare::class);
    }

    public function dnsRecords(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CloudflareDnsRecord::class);
    }

    public function accessTokens(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CloudflareAccess::class);
    }
}
