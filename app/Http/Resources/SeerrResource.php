<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SeerrResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'name' => $this['name'] ?? 'Seerr',
            'icon' => $this['icon'] ?? '',
            'status' => $this['status'] ?? 'Running',
            'requests' => $this['requests'] ?? [],
            'newShows' => $this['newShows'] ?? [],
            'totalRequests' => $this['totalRequests'] ?? 0,
            'totalMedia' => $this['totalMedia'] ?? 0,
            'url' => $this['url'] ?? '',
        ];
    }
}
