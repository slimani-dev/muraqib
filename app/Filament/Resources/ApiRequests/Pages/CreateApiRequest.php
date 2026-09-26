<?php

namespace App\Filament\Resources\ApiRequests\Pages;

use App\Filament\Resources\ApiRequests\ApiRequestResource;
use Filament\Resources\Pages\CreateRecord;

class CreateApiRequest extends CreateRecord
{
    protected static string $resource = ApiRequestResource::class;
}
