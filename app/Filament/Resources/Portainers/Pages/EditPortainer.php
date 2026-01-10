<?php

namespace App\Filament\Resources\Portainers\Pages;

use App\Filament\Resources\Portainers\PortainerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPortainer extends EditRecord
{
    protected static string $resource = PortainerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
