<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CloudflareAccess extends Model
{
    protected $table = 'cloudflare_access_tokens';

    protected $fillable = [
        'cloudflare_domain_id',
        'app_id',
        'name',
        'client_id',
        'service_token_id',
        'client_secret',
        'policy_id',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(CloudflareDomain::class, 'cloudflare_domain_id');
    }
}
