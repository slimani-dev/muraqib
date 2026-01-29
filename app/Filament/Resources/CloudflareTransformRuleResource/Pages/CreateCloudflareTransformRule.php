<?php

namespace App\Filament\Resources\CloudflareTransformRuleResource\Pages;

use App\Actions\Cloudflare\SyncTransformRules;
use App\Filament\Resources\CloudflareTransformRuleResource;
use App\Services\Cloudflare\CloudflareService;
use Filament\Resources\Pages\CreateRecord;

class CreateCloudflareTransformRule extends CreateRecord
{
    protected static string $resource = CloudflareTransformRuleResource::class;

    protected function afterCreate(): void
    {
        // Trigger Sync
        $service = app(CloudflareService::class);
        $action = new SyncTransformRules($service);
        $action->handle($this->record);
    }
}
