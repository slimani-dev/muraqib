<?php

namespace App\Models;

use App\Enums\UpdateStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Container extends Model
{
    use HasFactory;

    protected $fillable = [
        'portainer_id',
        'container_id',
        'image_id',
        'image_digest',
        'update_status',
        'update_checked_at',
        'current_release',
        'available_tags',
        'latest_digest',
        'update_error',
        'name',
        'image',
        'state',
        'status',
        'icon',
        'stack_name',
        'created_at_portainer',
        'display_name',
        'url',
        'description',
        'is_main',
        'endpoint_id',
        'endpoint_name',
    ];

    protected function casts(): array
    {
        return [
            'created_at_portainer' => 'datetime',
            'update_checked_at' => 'datetime',
            'update_status' => UpdateStatus::class,
            'is_main' => 'boolean',
            'available_tags' => 'array',
        ];
    }

    public function portainer(): BelongsTo
    {
        return $this->belongsTo(Portainer::class);
    }
}
