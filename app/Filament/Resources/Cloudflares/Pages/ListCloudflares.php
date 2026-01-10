<?php

namespace App\Filament\Resources\Cloudflares\Pages;

use App\Filament\Resources\Cloudflares\CloudflareResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCloudflares extends ListRecords
{
    protected static string $resource = CloudflareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
