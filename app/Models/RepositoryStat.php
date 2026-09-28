<?php

namespace App\Models;

use Database\Factories\RepositoryStatFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One day of a repository's counters, for deltas and sparklines.
 */
class RepositoryStat extends Model
{
    /** @use HasFactory<RepositoryStatFactory> */
    use HasFactory;

    protected $fillable = [
        'repository_id',
        'date',
        'stars',
        'forks',
        'downloads',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
