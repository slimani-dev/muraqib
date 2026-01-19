<?php

namespace App\Filament\Resources\Netdatas\Pages;

use App\Filament\Resources\Netdatas\NetdataResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNetdata extends EditRecord
{
    protected static string $resource = NetdataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
