<?php

namespace App\Filament\Resources\Portainers\Pages;

use App\Filament\Resources\Portainers\PortainerResource;
use App\Models\Portainer;
use App\Services\PortainerService;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPortainer extends ViewRecord
{
    protected static string $resource = PortainerResource::class;

    protected ?string $pollingInterval = '10s';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync')
                ->label('Sync')
                ->icon('heroicon-o-arrow-path')
                ->action(function (Portainer $record) {
                    $service = new PortainerService($record);
                    $results = $service->sync();

                    if (! $results['connected']) {
                        \Filament\Notifications\Notification::make()
                            ->title('Connection Failed')
                            ->body('Could not connect to Portainer API.')
                            ->danger()
                            ->send();

                        return;
                    }

                    \Filament\Notifications\Notification::make()
                        ->title('Sync completed')
                        ->body('Portainer information has been updated.')
                        ->success()
                        ->send();

                    if ($results['update_info'] && $results['update_info']['update_available']) {
                        \Filament\Notifications\Notification::make()
                            ->title('Update Available!')
                            ->body("Current: {$results['update_info']['current']} | Latest: {$results['update_info']['latest']}")
                            ->info()
                            ->actions([
                                Action::make('view')
                                    ->button()
                                    ->url($results['update_info']['release_url'], shouldOpenInNewTab: true),
                            ])
                            ->send();
                    }
                }),
        ];
    }
}
