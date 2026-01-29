<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareTransformRule extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUlids;

    protected $fillable = [
        'name',
        'cloudflare_id',
        'pattern',
        'headers',
        'rule_ids',
    ];

    protected $casts = [
        'rule_ids' => 'array',
        'headers' => 'array',
    ];

    public function cloudflare(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Cloudflare::class);
    }

    public function netdatas(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(Netdata::class, 'cloudflare_transform_ruleable');
    }

    public function portainers(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphedByMany(Portainer::class, 'cloudflare_transform_ruleable');
    }
}
