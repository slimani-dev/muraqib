<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareAccess extends Model
{
    protected $table = 'cloudflare_access_tokens';

    protected $fillable = [
        'cloudflare_domain_id',
        'app_id',
        'name',
        'client_id',
        'client_secret',
        'policy_id',
    ];

    protected $casts = [
        'client_secret' => 'encrypted',
    ];

    public function domain()
    {
        return $this->belongsTo(CloudflareDomain::class, 'cloudflare_domain_id');
    }
}
