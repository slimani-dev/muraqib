<?php

namespace App\Models;

use App\Rules\DashboardLayout;
use Database\Factories\DashboardPageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * One page of a team's dashboard, shared by everyone on the team. Every team has one
 * default page (shown at /{team}/dashboard; it can be renamed but not deleted).
 * The layout is validated by {@see DashboardLayout}.
 *
 * @property string $name
 * @property string $slug
 * @property bool $is_default
 * @property ?array{version: int, zones: array{left: list<array<string, mixed>>, middle: list<array<string, mixed>>, right: list<array<string, mixed>>}} $layout
 */
class DashboardPage extends Model
{
    /** @use HasFactory<DashboardPageFactory> */
    use HasFactory;

    protected $fillable = [
        'team_id',
        'name',
        'slug',
        'is_default',
        'position',
        'layout',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'position' => 'integer',
            'layout' => 'array',
        ];
    }

    /**
     * The default page can be renamed but never deleted: every team keeps one at /{team}/dashboard.
     */
    protected static function booted(): void
    {
        static::deleting(function (DashboardPage $page): void {
            if ($page->is_default && $page->team()->exists()) {
                throw new LogicException('The default dashboard page cannot be deleted.');
            }
        });

        static::updating(function (DashboardPage $page): void {
            if ($page->isDirty('is_default') && $page->getOriginal('is_default')) {
                throw new LogicException('The default dashboard page cannot stop being the default.');
            }
        });
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * An empty layout, for new pages.
     *
     * @return array{version: int, zones: array{left: list<mixed>, middle: list<mixed>, right: list<mixed>}}
     */
    public static function emptyLayout(): array
    {
        return ['version' => 1, 'zones' => ['left' => [], 'middle' => [], 'right' => []]];
    }
}
