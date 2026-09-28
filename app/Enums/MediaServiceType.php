<?php

namespace App\Enums;

use App\Services\Media\BazarrClient;
use App\Services\Media\JellyfinClient;
use App\Services\Media\MediaClient;
use App\Services\Media\RadarrClient;
use App\Services\Media\SeerrClient;
use App\Services\Media\SonarrClient;
use App\Services\Media\TransmissionClient;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MediaServiceType: string implements HasIcon, HasLabel
{
    case Jellyfin = 'jellyfin';
    case Seerr = 'seerr';
    case Radarr = 'radarr';
    case Sonarr = 'sonarr';
    case Bazarr = 'bazarr';
    case Transmission = 'transmission';

    public function getLabel(): string
    {
        return match ($this) {
            self::Jellyfin => 'Jellyfin',
            self::Seerr => 'Seerr',
            self::Radarr => 'Radarr',
            self::Sonarr => 'Sonarr',
            self::Bazarr => 'Bazarr',
            self::Transmission => 'Transmission',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Jellyfin => 'si-jellyfin',
            self::Seerr => 'mdi-movie-search',
            self::Radarr => 'si-radarr',
            self::Sonarr => 'si-sonarr',
            self::Bazarr => 'mdi-subtitles',
            self::Transmission => 'si-transmission',
        };
    }

    /**
     * Transmission authenticates with a username and password instead of an API key.
     */
    public function usesBasicAuth(): bool
    {
        return $this === self::Transmission;
    }

    /**
     * @return class-string<MediaClient>
     */
    public function clientClass(): string
    {
        return match ($this) {
            self::Jellyfin => JellyfinClient::class,
            self::Seerr => SeerrClient::class,
            self::Radarr => RadarrClient::class,
            self::Sonarr => SonarrClient::class,
            self::Bazarr => BazarrClient::class,
            self::Transmission => TransmissionClient::class,
        };
    }
}
