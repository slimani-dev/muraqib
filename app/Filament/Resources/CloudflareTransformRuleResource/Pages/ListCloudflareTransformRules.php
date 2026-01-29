<?php

namespace App\Filament\Resources\CloudflareTransformRuleResource\Pages;

use App\Filament\Resources\CloudflareTransformRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCloudflareTransformRules extends ListRecords
{
    protected static string $resource = CloudflareTransformRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
