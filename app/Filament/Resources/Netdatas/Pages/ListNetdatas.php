<?php

namespace App\Filament\Resources\Netdatas\Pages;

use App\Filament\Resources\Netdatas\NetdataResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNetdatas extends ListRecords
{
    protected static string $resource = NetdataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
