<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Container extends Model
{
    use HasFactory;

    protected $fillable = [
        'portainer_id',
        'container_id',
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

    protected $casts = [
        'created_at_portainer' => 'datetime',
        'is_main' => 'boolean',
    ];

    public function portainer(): BelongsTo
    {
        return $this->belongsTo(Portainer::class);
    }
}
