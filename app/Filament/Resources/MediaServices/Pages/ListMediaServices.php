<?php

namespace App\Filament\Resources\MediaServices\Pages;

use App\Filament\Resources\MediaServices\MediaServiceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMediaServices extends ListRecords
{
    protected static string $resource = MediaServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
