<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JellyfinResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'] ?? 'Jellyfin',
            'icon' => $this['icon'] ?? '',
            'status' => $this['status'] ?? 'Running',
            'url' => $this['url'] ?? '',
            'nowPlaying' => $this['nowPlaying'] ?? null,
            'users' => $this['users'] ?? [],
            'recentlyPlayedByUser' => $this['recentlyPlayedByUser'] ?? [],
            'nextUpByUser' => $this['nextUpByUser'] ?? [],
        ];
    }
}
