<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Portainer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'url',
        'access_token',
        'status',
        'version',
        'latest_version',
        'uptime',
        'last_synced_at',
        'data',
    ];

    protected $casts = [
        'status' => \App\Enums\PortainerStatus::class,
        'last_synced_at' => 'datetime',
        'data' => 'array',
    ];

    public function stacks()
    {
        return $this->hasMany(\App\Models\Stack::class);
    }

    public function containers()
    {
        return $this->hasMany(\App\Models\Container::class);
    }
}
