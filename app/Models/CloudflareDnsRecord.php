<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CloudflareDnsRecord extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

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

    /**
     * Get the Zero Trust Access Token configuration for this DNS record (by name matching).
     */
    public function access()
    {
        // Since we don't have a direct foreign key, we can matches on domain_id AND name.
        // However, standard hasOne needs keys.
        // We can use a composite key approach if supported or just returns the builder for manual use.
        // Or simpler: define it as hasOne but we need to ensure the keys match.
        // 'cloudflare_domain_id' matches 'cloudflare_domain_id'.
        // But 'name' must also match.
        // Standard Laravel relationships don't support multi-column foreign keys easily without packages.
        // For now, let's add a helper method instead of a Relation object to avoid issues,
        // OR use a pseudo-relationship if we use it for existence checks (like subqueries).

        return $this->hasOne(CloudflareAccess::class, 'cloudflare_domain_id', 'cloudflare_domain_id')
            ->where('name', $this->name);
    }
}
