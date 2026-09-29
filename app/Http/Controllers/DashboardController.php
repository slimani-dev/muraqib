<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\MediaService;
use App\Models\Netdata;
use App\Services\Git\GitHubInbox;
use App\Services\Git\RepositoryDashboard;
use App\Services\Media\MediaStatusChecker;
use App\Services\MediaArrService;
use App\Services\NetworkLatencyService;
use App\Settings\WeatherSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Current weather and forecast from Open-Meteo for the location in the admin panel
     * (Settings → Weather), with an optional Unsplash background. Null without a location.
     */
    private function getWeatherData()
    {
        $settings = app(WeatherSettings::class);

        if (! $settings->hasLocation()) {
            return null;
        }

        $latitude = $settings->latitude;
        $longitude = $settings->longitude;

        if (Cache::has('weather_sba')) {
            return Cache::get('weather_sba');
        }

        try {
            $response = Http::timeout(5)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
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
                    $unsplashKey = $settings->unsplash_key;
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

                    $unsplashResponse = blank($unsplashKey) ? null : Http::timeout(5)->get('https://api.unsplash.com/photos/random', [
                        'client_id' => $unsplashKey,
                        'query' => trim($settings->location_name.' '.$keyword),
                        'orientation' => 'landscape',
                    ]);

                    if ($unsplashResponse?->successful()) {
                        $unsplashData = $unsplashResponse->json();
                        $data['background_image'] = $unsplashData['urls']['regular'] ?? null;
                    }
                } catch (\Exception $e) {
                    // Ignore Unsplash errors so weather still loads
                }

                $data['location_name'] = $settings->location_name;

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

    /**
     * A dashboard page: the team's default page, or the one named by its slug.
     */
    public function index(Request $request, MediaArrService $mediaService, string $currentTeam, ?string $page = null)
    {
        $team = $request->user()->currentTeam;
        $dashboardPage = $page === null
            ? $team->defaultDashboardPage()
            : $team->dashboardPages()->where('slug', $page)->firstOrFail();

        return Inertia::render('Dashboard', [
            ...$this->dashboardProps($mediaService),
            'dashboardPage' => $dashboardPage->only(['id', 'name', 'slug', 'is_default', 'layout']),
        ]);
    }

    /**
     * Props for every dashboard widget. Also used by the widget preview page.
     *
     * @return array<string, mixed>
     */
    protected function dashboardProps(MediaArrService $mediaService): array
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

        return [
            'agenda_cached' => Cache::get('media_agenda'),
            'containers' => Container::with('portainer')->orderBy('name')->get(),

            ...$this->mediaProps($mediaService),

            'weather_cached' => Cache::get('weather_sba'),
            'weather' => Inertia::defer(fn () => $this->getWeatherData()),

            'netdata' => $netdataServers,

            // Synced every 30 minutes by the queue; read from the database only
            'repositories' => app(RepositoryDashboard::class)->repositories(),
            'repository_trends' => app(RepositoryDashboard::class)->trends(),
            'contributions' => app(RepositoryDashboard::class)->contributions(),
            // Fetched from GitHub (cached a minute), so it's deferred to keep the first paint fast
            'github_inbox' => Inertia::defer(fn () => app(GitHubInbox::class)->accounts()),

            // Only used when Netdata has no ping chart; cached 30 minutes, the widget's refresh button forces a new one
            'network_latency' => Inertia::defer(fn () => app(NetworkLatencyService::class)->latency($this->wantsFreshLatency())),
        ];
    }

    /**
     * Media widget props: one `media_{id}` (deferred) and `media_{id}_cached` pair per
     * enabled service. Polls reuse the cached data; a widget's refresh button sends
     * the X-Media-Refresh header (the service id) to bypass the cache, throttled per service.
     *
     * @return array<string, mixed>
     */
    protected function mediaProps(MediaArrService $mediaService): array
    {
        $statusChecker = app(MediaStatusChecker::class);

        $props = [
            'media_services' => $mediaService->services()->values()->map(fn (MediaService $service): array => [
                'id' => $service->id,
                'type' => $service->type->value,
                'name' => $service->name,
                'url' => $service->baseUrl(),
                'status' => $statusChecker->forDashboard($service),
            ])->all(),
            'agenda' => Inertia::defer(fn () => $mediaService->getAgendaData()),
        ];

        foreach ($mediaService->services() as $service) {
            $props["media_{$service->id}_cached"] = $mediaService->cachedData($service);
            $props["media_{$service->id}"] = Inertia::defer(
                fn () => $mediaService->data($service, $this->wantsFreshMediaData($service)),
            );
        }

        return $props;
    }

    /**
     * Whether this request is a manual refresh of the given service, allowed once every 10 seconds.
     */
    protected function wantsFreshMediaData(MediaService $service): bool
    {
        if (request()->header('X-Media-Refresh') !== (string) $service->id) {
            return false;
        }

        return RateLimiter::attempt("media-refresh:{$service->id}", 1, fn (): bool => true, 10) === true;
    }

    /**
     * Whether the network widget's refresh button asked for a new latency measurement, allowed once a minute.
     */
    protected function wantsFreshLatency(): bool
    {
        if (! request()->hasHeader('X-Latency-Refresh')) {
            return false;
        }

        return RateLimiter::attempt('network-latency-refresh', 1, fn (): bool => true, 60) === true;
    }
}
