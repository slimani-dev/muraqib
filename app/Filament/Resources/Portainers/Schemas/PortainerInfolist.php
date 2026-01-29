<?php

namespace App\Filament\Resources\Portainers\Schemas;

use App\Models\Portainer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Colors\Color;
use LaraZeus\TorchFilament\Infolists\TorchEntry;
use Webbingbrasil\FilamentCopyActions\Actions\CopyAction;

class PortainerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Update Available')
                    ->collapsed()
                    ->visible(fn (Portainer $record) => $record->latest_version && version_compare($record->latest_version, ltrim($record->version ?? '', 'v'), '>'))
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor(Color::Red)
                    ->schema(function () {
                        $dockerRunCommand = <<<BASH
                        # Stop and remove the old container
                        docker stop portainer
                        docker rm portainer

                        # Pull the latest image
                        docker pull portainer/portainer-ce:latest

                        # Re-run the container (example with docker run)
                        docker run -d -p 9000:9000 \
                            --name=portainer \
                            --restart=always \
                            -v /var/run/docker.sock:/var/run/docker.sock \
                            -v portainer_data:/data \
                            portainer/portainer-ce:latest

                        BASH;

                        return [
                            TextEntry::make('update_message')
                                ->hiddenLabel()
                                ->state(fn (Portainer $record) => "Update Available! Current: {$record->version} | Latest: {$record->latest_version}")
                                ->weight('bold')
                                ->size('lg')
                                ->url('https://github.com/portainer/portainer/releases/latest', shouldOpenInNewTab: true),

                            \CodeWithDennis\SimpleAlert\Components\SimpleAlert::make('volume_warning_alert')
                                ->warning()
                                ->title('Ensure that the volume name matches your original volume name (e.g. portainer_data).'),

                            Section::make()
                                ->schema([
                                    TorchEntry::make('docker_run_command')
                                        ->columnSpanFull()
                                        ->withGutter(false)
                                        ->grammar('shell')
                                        ->hintActions([CopyAction::make()->copyable($dockerRunCommand)])
                                        ->state($dockerRunCommand),
                                ]),
                        ];
                    }),

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
                            ->icon(fn (Portainer $record) => ($record->latest_version && ltrim($record->version ?? '', 'v') === $record->latest_version) ? 'heroicon-o-check-circle' : 'heroicon-o-information-circle')
                            ->color(fn (Portainer $record) => ($record->latest_version && ltrim($record->version ?? '', 'v') === $record->latest_version) ? 'success' : 'gray'),

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
                            ->state(fn (Portainer $record) => $record->data['stats']['endpoints_count'] ?? 0)
                            ->badge()
                            ->color('info'),

                        TextEntry::make('stacks_count')
                            ->label('Stacks')
                            ->state(fn (Portainer $record) => $record->stacks()->count())
                            ->badge()
                            ->color('success'),

                        TextEntry::make('containers_count')
                            ->label('Total Containers')
                            ->state(fn (Portainer $record) => $record->containers()->count())
                            ->badge()
                            ->color('warning'),

                        TextEntry::make('containers_running')
                            ->label('Running Containers')
                            ->state(fn (Portainer $record) => $record->containers()->where('state', 'running')->count())
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
