<?php

namespace App\Filament\Resources\Portainers\Schemas;

use App\Models\Portainer;
use App\Services\PortainerService;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PortainerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Update Available')
                    ->schema([
                        TextEntry::make('update_message')
                            ->hiddenLabel()
                            ->state(fn (Portainer $record) => "Update Available! Current: {$record->version} | Latest: {$record->latest_version}")
                            ->color('danger')
                            ->weight('bold')
                            ->size('lg')
                            ->icon('heroicon-o-exclamation-triangle')
                            ->url('https://github.com/portainer/portainer/releases/latest', shouldOpenInNewTab: true),
                    ])
                    ->visible(fn (Portainer $record) => $record->latest_version && version_compare($record->latest_version, ltrim($record->version ?? '', 'v'), '>'))
                    ->icon('heroicon-o-rocket-launch'),

                Section::make('Connection & Status')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Instance Name')
                            ->weight('bold')
                            ->size('lg'),

                        TextEntry::make('url')
                            ->label('API URL')
                            ->copyable()
                            ->url(fn (Portainer $record) => $record->url, shouldOpenInNewTab: true),

                        TextEntry::make('status')
                            ->badge(),

                        TextEntry::make('version')
                            ->label('Portainer Version')
                            ->placeholder('Not synced yet')
                            ->icon('heroicon-o-information-circle'),

                        TextEntry::make('last_synced_at')
                            ->dateTime()
                            ->since()
                            ->placeholder('Never synced'),
                    ])
                    ->columns(2),

                Section::make('Statistics')
                    ->schema([
                        TextEntry::make('endpoints_count')
                            ->label('Endpoints')
                            ->state(function (Portainer $record) {
                                try {
                                    $service = new PortainerService($record);
                                    $stats = $service->getStats();

                                    return $stats['endpoints_count'] ?? 0;
                                } catch (\Exception $e) {
                                    return 'N/A';
                                }
                            })
                            ->badge()
                            ->color('info'),

                        TextEntry::make('stacks_count')
                            ->label('Stacks')
                            ->state(function (Portainer $record) {
                                try {
                                    $service = new PortainerService($record);
                                    $stats = $service->getStats();

                                    return $stats['stacks_count'] ?? 0;
                                } catch (\Exception $e) {
                                    return 'N/A';
                                }
                            })
                            ->badge()
                            ->color('success'),

                        TextEntry::make('containers_count')
                            ->label('Total Containers')
                            ->state(function (Portainer $record) {
                                try {
                                    $service = new PortainerService($record);
                                    $stats = $service->getStats();

                                    return $stats['containers_count'] ?? 0;
                                } catch (\Exception $e) {
                                    return 'N/A';
                                }
                            })
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('containers_running')
                            ->label('Running Containers')
                            ->state(function (Portainer $record) {
                                try {
                                    $service = new PortainerService($record);
                                    $stats = $service->getStats();

                                    return $stats['containers_running'] ?? 0;
                                } catch (\Exception $e) {
                                    return 'N/A';
                                }
                            })
                            ->badge()
                            ->color('success'),
                    ])
                    ->columns(4),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('created_at')
                            ->dateTime()
                            ->since(),

                        TextEntry::make('updated_at')
                            ->dateTime()
                            ->since(),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
