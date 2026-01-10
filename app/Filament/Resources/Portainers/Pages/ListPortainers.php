<?php

namespace App\Filament\Resources\Portainers\Pages;

use App\Filament\Resources\Portainers\PortainerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPortainers extends ListRecords
{
    protected static string $resource = PortainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
