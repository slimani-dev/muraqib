<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortainerEndpoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'portainer_id',
        'endpoint_id',
        'name',
        'url',
        'type',
        'status',
    ];

    public function portainer()
    {
        return $this->belongsTo(Portainer::class);
    }

    public function stacks()
    {
        return $this->hasMany(Stack::class, 'endpoint_id', 'endpoint_id'); // Linking via external endpoint_id if that's how stacks are related, or local id?
        // Checking Stack model: it has 'endpoint_id' (integer) and 'portainer_id'.
        // The stacks are fetched per endpoint in the seeder.
        // Stack model logic seems to rely on external endpoint_id.
    }
}
