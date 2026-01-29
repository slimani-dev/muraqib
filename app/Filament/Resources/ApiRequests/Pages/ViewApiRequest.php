<?php

namespace App\Filament\Resources\ApiRequests\Pages;

use App\Filament\Resources\ApiRequests\ApiRequestResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewApiRequest extends ViewRecord
{
    protected static string $resource = ApiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
