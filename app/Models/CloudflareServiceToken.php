<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CloudflareServiceToken extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'account_id',
        'token_id',
        'name',
        'client_id',
        'client_secret', // Encrypted via Casts or Accessor?
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'client_secret' => 'encrypted',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Cloudflare::class, 'account_id');
    }

    public function policies(): BelongsToMany
    {
        return $this->belongsToMany(CloudflareAccessPolicy::class, 'cloudflare_access_policy_service_token', 'service_token_id', 'policy_id');
    }
}
