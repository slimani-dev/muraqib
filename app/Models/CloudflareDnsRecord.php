<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareDnsRecord extends Model
{
    protected $fillable = [
        'cloudflare_domain_id',
        'record_id',
        'type',
        'name',
        'content',
        'proxied',
        'ttl',
    ];

    protected $casts = [
        'proxied' => 'boolean',
    ];

    public function domain(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CloudflareDomain::class, 'cloudflare_domain_id');
    }
}
