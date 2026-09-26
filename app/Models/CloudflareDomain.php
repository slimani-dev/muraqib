<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'cloudflare_id',
        'zone_id',
        'name',
        'status',
    ];

    public function cloudflare(): BelongsTo
    {
        return $this->belongsTo(Cloudflare::class);
    }

    public function dnsRecords(): HasMany
    {
        return $this->hasMany(CloudflareDnsRecord::class);
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(CloudflareAccess::class);
    }
}
