<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiRequest extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'service',
        'name',
        'method',
        'url',
        'request_headers',
        'request_body',
        'status_code',
        'response_headers',
        'response_body',
        'duration_ms',
        'error',
        'user_id',
        'ip_address',
        'meta',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
