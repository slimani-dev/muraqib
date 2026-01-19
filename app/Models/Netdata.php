<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Netdata extends Model
{
    protected $fillable = [
        'cloudflare_access_id',
        'cloudflare_ingress_rule_id',
        'name',
        'status',
        'disk_settings',
        'network_settings',
    ];

    protected function casts(): array
    {
        return [
            'disk_settings' => 'array',
            'network_settings' => 'array',
        ];
    }

    public function access(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CloudflareAccess::class, 'cloudflare_access_id');
    }

    public function ingressRule(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(CloudflareIngressRule::class, 'cloudflare_ingress_rule_id');
    }
}
