<?php

namespace App\Filament\Resources\Cloudflares\Pages;

use App\Filament\Resources\Cloudflares\CloudflareResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCloudflare extends EditRecord
{
    protected static string $resource = CloudflareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
