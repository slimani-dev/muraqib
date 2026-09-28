<?php

namespace App\Http\Controllers\Api;

use App\Enums\MediaServiceType;
use App\Http\Controllers\Controller;
use App\Http\Resources\JellyfinResource;
use App\Http\Resources\SeerrResource;
use App\Models\MediaService;
use App\Services\MediaArrService;
use Illuminate\Http\JsonResponse;

class MediaApiController extends Controller
{
    /**
     * Dashboard data for one media service. Pass `fresh=1` to bypass the cache.
     */
    public function show(MediaService $mediaService, MediaArrService $media): JsonResponse
    {
        abort_unless($mediaService->is_enabled, 404, "{$mediaService->name} is disabled.");

        $data = $media->data($mediaService, request()->boolean('fresh'));

        return response()->json(match ($mediaService->type) {
            MediaServiceType::Jellyfin => new JellyfinResource($data),
            MediaServiceType::Seerr => new SeerrResource($data),
            default => $data,
        });
    }
}
