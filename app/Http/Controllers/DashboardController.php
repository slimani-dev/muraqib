<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\Netdata;
use App\Services\MediaArrService;
use App\Services\NetdataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class DashboardController extends Controller
{
    private function getWeatherData()
    {
        if (Cache::has('weather_sba')) {
            return Cache::get('weather_sba');
        }

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => 35.2106,
                'longitude' => -0.6300,
                'current' => 'temperature_2m,is_day,weather_code,relative_humidity_2m,precipitation_probability,wind_speed_10m',
                'hourly' => 'temperature_2m,precipitation_probability,wind_speed_10m,wind_direction_10m',
                'daily' => 'weather_code,temperature_2m_max,temperature_2m_min',
                'timezone' => 'auto',
                'forecast_days' => 7,
                'forecast_hours' => 24,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                // Fetch Unsplash background image
                try {
                    $unsplashKey = env('UNSPLASH_API_KEY', '');
                    $isDay = $data['current']['is_day'] ?? 1;
                    $weatherCode = $data['current']['weather_code'] ?? 0;
                    $timeOfDay = $isDay ? 'daytime' : 'night';

                    $keyword = "weather $timeOfDay";
                    if ($weatherCode == 0) {
                        $keyword = "clear sky $timeOfDay";
                    } elseif (in_array($weatherCode, [1, 2, 3])) {
                        $keyword = "cloudy sky $timeOfDay";
                    } elseif (in_array($weatherCode, [45, 48])) {
                        $keyword = "fog $timeOfDay";
                    } elseif (in_array($weatherCode, [51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82])) {
                        $keyword = "rain $timeOfDay";
                    } elseif (in_array($weatherCode, [71, 73, 75, 77, 85, 86])) {
                        $keyword = "snow $timeOfDay";
                    } elseif (in_array($weatherCode, [95, 96, 99])) {
                        $keyword = "thunderstorm $timeOfDay";
                    }

                    $unsplashResponse = Http::timeout(5)->get('https://api.unsplash.com/photos/random', [
                        'client_id' => $unsplashKey,
                        'query' => "Sidi Bel Abbes $keyword",
                        'orientation' => 'landscape',
                    ]);

                    if ($unsplashResponse->successful()) {
                        $unsplashData = $unsplashResponse->json();
                        $data['background_image'] = $unsplashData['urls']['regular'] ?? null;
                    }
                } catch (\Exception $e) {
                    // Ignore Unsplash errors so weather still loads
                }

                Cache::put('weather_sba', $data, 1800);

                return $data;
            }
        } catch (\Exception $e) {
            // ignore
        }

        return null;
    }

    public function refreshWeather()
    {
        Cache::forget('weather_sba');

        return back();
    }

    public function index(MediaArrService $mediaService, NetdataService $netdataService)
    {
        $netdataServers = Netdata::with(['access', 'ingressRule'])
            ->where('status', 'active')
            ->get()
            ->map(function ($server) {
                $static = Cache::get("netdata_static_{$server->id}");

                return [
                    'id' => $server->id,
                    'name' => $server->name,
                    'status' => $server->status,
                    'type' => $server->type ?? 'Server',
                    'stats' => $static ? array_merge($static, ['is_partial' => true]) : null,
                ];
            });

        return Inertia::render('Dashboard', [
            'agenda_cached' => Cache::get('media_agenda'),
            'containers' => Container::with('portainer')->orderBy('name')->get(),

            // Cached data for instant load
            'jellyfin_cached' => $mediaService->getJellyfinCachedData(),
            'seerr_cached' => Cache::get('seerr_data'),
            'radarr_cached' => Cache::get('radarr_data'),
            'sonarr_cached' => Cache::get('sonarr_data'),
            'bazarr_cached' => Cache::get('bazarr_data'),
            'transmission_cached' => Cache::get('transmission_data'),

            // Deferred fresh data fetching
            'jellyfin' => Inertia::defer(fn () => $mediaService->getJellyfinData(true, false)),
            'seerr' => Inertia::defer(fn () => $mediaService->getSeerrData(true)),
            'radarr' => Inertia::defer(fn () => $mediaService->getRadarrData(true)),
            'sonarr' => Inertia::defer(fn () => $mediaService->getSonarrData(true)),
            'bazarr' => Inertia::defer(fn () => $mediaService->getBazarrData(true)),
            'transmission' => Inertia::defer(fn () => $mediaService->getTransmissionData(true)),
            'agenda' => Inertia::defer(fn () => $mediaService->getAgendaData()),

            'weather_cached' => Cache::get('weather_sba'),
            'weather' => Inertia::defer(fn () => $this->getWeatherData()),

            'netdata' => $netdataServers,
        ]);
    }
}
