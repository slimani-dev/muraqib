<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CloudflareAccessApplication extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'account_id',
        'app_id',
        'name',
        'domain',
        'type',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Cloudflare::class, 'account_id');
    }

    public function policies(): HasMany
    {
        return $this->hasMany(CloudflareAccessPolicy::class, 'application_id');
    }
}
