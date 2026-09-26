<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\JellyfinResource;
use App\Http\Resources\SeerrResource;
use App\Services\MediaArrService;
use Illuminate\Support\Facades\Cache;

class MediaApiController extends Controller
{
    public function jellyfin(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('jellyfin_data')) {
            sleep(2);
        }

        return response()->json(new JellyfinResource($mediaService->getJellyfinData(
            request()->boolean('fresh_sessions'),
            request()->boolean('fresh_static')
        )));
    }

    public function seerr(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('seerr_data')) {
            sleep(2);
        }

        return response()->json(new SeerrResource($mediaService->getSeerrData(request()->boolean('fresh'))));
    }

    public function radarr(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('radarr_data')) {
            sleep(2);
        }

        return response()->json($mediaService->getRadarrData(request()->boolean('fresh')));
    }

    public function sonarr(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('sonarr_data')) {
            sleep(2);
        }

        return response()->json($mediaService->getSonarrData(request()->boolean('fresh')));
    }

    public function bazarr(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('bazarr_data')) {
            sleep(2);
        }

        return response()->json($mediaService->getBazarrData(request()->boolean('fresh')));
    }

    public function transmission(MediaArrService $mediaService)
    {
        if (app()->environment('local') && ! Cache::has('transmission_data')) {
            sleep(2);
        }

        return response()->json($mediaService->getTransmissionData(request()->boolean('fresh')));
    }
}
