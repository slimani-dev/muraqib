<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Portainer extends Model
{
    protected $fillable = [
        'name',
        'url',
        'access_token',
        'status',
    ];

    protected $casts = [
        'status' => \App\Enums\PortainerStatus::class,
    ];
}
