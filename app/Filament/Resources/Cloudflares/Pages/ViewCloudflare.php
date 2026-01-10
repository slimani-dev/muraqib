<?php

namespace App\Filament\Resources\Cloudflares\Pages;

use App\Filament\Resources\Cloudflares\CloudflareResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCloudflare extends ViewRecord
{
    protected static string $resource = CloudflareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
