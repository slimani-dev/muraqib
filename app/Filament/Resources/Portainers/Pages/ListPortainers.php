<?php

namespace App\Filament\Resources\Portainers\Pages;

use App\Filament\Resources\Portainers\PortainerResource;
use App\Models\Portainer;
use App\Services\PortainerService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPortainers extends ListRecords
{
    protected static string $resource = PortainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // TODO get back to this wizard when we setup the stacks repo to auto install default stacks here

            \Filament\Actions\Action::make('setup_wizard')
                ->label('Setup Wizard')
                ->icon('mdi-wizard-hat')
                ->slideOver(false)
                ->modalWidth('7xl'),

            CreateAction::make()
                ->createAnother(false)
                ->before(function (CreateAction $action, array $data) {
                    if (($data['connection_status'] ?? null) !== 'success') {
                        $data['connection_status'] = 'error';
                        $data['connection_message'] = 'Please test the connection before creating.';

                        $action->getLivewire()->getMountedActionSchema()->fill($data);

                        $action->halt();
                    }
                })
                ->after(function (Portainer $record) {
                    $service = new PortainerService($record);
                    $results = $service->sync();
                }),
        ];
    }
}
