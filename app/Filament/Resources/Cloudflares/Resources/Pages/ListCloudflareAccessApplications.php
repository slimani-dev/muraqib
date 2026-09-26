<?php

namespace App\Filament\Resources\Cloudflares\Resources\Pages;

use App\Filament\Resources\Cloudflares\Resources\CloudflareAccessApplicationResource;
use Filament\Resources\Pages\ListRecords;

class ListCloudflareAccessApplications extends ListRecords
{
    protected static string $resource = CloudflareAccessApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action here, handled in Relation Manager
        ];
    }
}
