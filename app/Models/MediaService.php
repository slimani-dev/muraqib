<?php

namespace App\Models;

use App\Enums\MediaServiceType;
use App\Rules\PublicUrl;
use App\Services\Media\MediaClient;
use Database\Factories\MediaServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A media app (Jellyfin, Seerr, the *arr apps, Transmission) the dashboard reads from.
 *
 * @property MediaServiceType $type
 * @property string $name
 * @property string $url
 * @property ?string $api_key
 * @property ?string $username
 * @property ?string $password
 * @property ?array<string, mixed> $settings
 * @property ?string $status_url
 * @property ?array<string, string> $status_headers
 * @property bool $is_enabled
 */
class MediaService extends Model
{
    /** @use HasFactory<MediaServiceFactory> */
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'url',
        'api_key',
        'username',
        'password',
        'settings',
        'status_url',
        'status_headers',
        'is_enabled',
    ];

    protected $hidden = [
        'api_key',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'type' => MediaServiceType::class,
            'api_key' => 'encrypted',
            'password' => 'encrypted',
            'settings' => 'array',
            'status_headers' => 'encrypted:array',
            'is_enabled' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        $forgetCache = function (MediaService $service): void {
            foreach ($service->cacheKeys() as $key) {
                Cache::forget($key);
            }
        };

        static::saved($forgetCache);
        static::deleted($forgetCache);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function enabled(Builder $query): void
    {
        $query->where('is_enabled', true);
    }

    /**
     * The first enabled service of a type. Several services of one type may exist.
     */
    public static function forType(MediaServiceType $type): ?self
    {
        return static::query()->enabled()->where('type', $type)->orderBy('id')->first();
    }

    public function baseUrl(): string
    {
        return rtrim($this->url, '/');
    }

    public function client(): MediaClient
    {
        $clientClass = $this->type->clientClass();

        return new $clientClass($this);
    }

    /**
     * The public URL whose accessibility the dashboard shows, or null when there is none.
     */
    public function statusCheckUrl(): ?string
    {
        $url = $this->status_url ?: $this->url;

        return PublicUrl::isPublic($url) ? $url : null;
    }

    /**
     * Headers (usually a secret token) or a plain-http URL can't be checked from an
     * HTTPS page, so those checks run on the server instead of the browser.
     */
    public function statusCheckRunsOnServer(): bool
    {
        return filled($this->status_headers)
            || str_starts_with((string) $this->statusCheckUrl(), 'http://');
    }

    /**
     * Cache key for one piece of this service's data (e.g. `data`, `sessions`).
     */
    public function cacheKey(string $name): string
    {
        return "media_{$this->id}_{$name}";
    }

    public function statusCacheKey(): string
    {
        return $this->cacheKey('status');
    }

    /**
     * Every cache key this service's data lives under, cleared when it changes.
     *
     * @return list<string>
     */
    public function cacheKeys(): array
    {
        $keys = match ($this->type) {
            MediaServiceType::Jellyfin => [$this->cacheKey('sessions'), $this->cacheKey('static')],
            default => [$this->cacheKey('data')],
        };

        if (in_array($this->type, [MediaServiceType::Radarr, MediaServiceType::Sonarr], true)) {
            $keys[] = 'media_agenda';
        }

        return [...$keys, $this->statusCacheKey()];
    }
}
