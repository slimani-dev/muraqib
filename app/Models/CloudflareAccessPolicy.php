<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CloudflareAccessPolicy extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'application_id',
        'policy_id',
        'name',
        'decision',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(CloudflareAccessApplication::class, 'application_id');
    }

    public function serviceTokens(): BelongsToMany
    {
        return $this->belongsToMany(CloudflareServiceToken::class, 'cloudflare_access_policy_service_token', 'policy_id', 'service_token_id');
    }
}
