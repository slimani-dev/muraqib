<?php

namespace App\Filament\Clusters\Settings;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

/**
 * App-wide settings, one page per area (Weather, ...).
 */
class SettingsCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    public static function getNavigationLabel(): string
    {
        return 'Settings';
    }

    public static function getClusterBreadcrumb(): string
    {
        return 'Settings';
    }
}
