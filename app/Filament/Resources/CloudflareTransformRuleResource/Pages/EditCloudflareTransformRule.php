<?php

namespace App\Filament\Resources\CloudflareTransformRuleResource\Pages;

use App\Actions\Cloudflare\SyncTransformRules;
use App\Filament\Resources\CloudflareTransformRuleResource;
use App\Services\Cloudflare\CloudflareService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCloudflareTransformRule extends EditRecord
{
    protected static string $resource = CloudflareTransformRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\Action::make('deploy')
                ->label('Redeploy to Cloudflare')
                ->action(function () {
                    $service = app(CloudflareService::class);
                    $action = new SyncTransformRules($service);
                    $action->handle($this->record);

                    \Filament\Notifications\Notification::make()
                        ->title('Rules Deployed')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function afterSave(): void
    {
        // Trigger Sync
        $service = app(CloudflareService::class);
        $action = new SyncTransformRules($service);
        $action->handle($this->record);
    }
}
