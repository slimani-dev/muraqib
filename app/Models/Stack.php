<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stack extends Model
{
    use HasFactory;

    protected $fillable = [
        'portainer_id',
        'external_id',
        'name',
        'endpoint_id',
        'stack_status',
        'stack_type',
        'icon',
        'created_at_portainer',
        'stack_file_content',
        'env',
    ];

    protected $casts = [
        'endpoint_id' => 'integer',
        'stack_status' => 'integer',
        'stack_type' => 'integer',
        'created_at_portainer' => 'datetime',
        'env' => 'array',
    ];

    public function portainer()
    {
        return $this->belongsTo(Portainer::class);
    }
}
