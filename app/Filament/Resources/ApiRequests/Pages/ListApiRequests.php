<?php

namespace App\Filament\Resources\ApiRequests\Pages;

use App\Filament\Resources\ApiRequests\ApiRequestResource;
use App\Models\ApiRequest;
use App\Services\ApiMonitor\LoggingMiddleware;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Http;

class ListApiRequests extends ListRecords
{
    protected static string $resource = ApiRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->slideOver()
                ->extraModalFooterActions(fn (CreateAction $action): array => [
                    $action->makeModalSubmitAction('run', arguments: ['run' => true])
                        ->label('Create & Run')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning'),
                ])
                ->after(function (ApiRequest $record, array $arguments) {
                    if ($arguments['run'] ?? false) {
                        try {
                            $request = Http::withMiddleware(
                                new LoggingMiddleware($record->service ?? 'API Runner', 'Create & Run')
                            );

                            if (! empty($record->request_headers)) {
                                $request->withHeaders($record->request_headers);
                            }

                            if (! empty($record->request_body)) {
                                $bodyStr = is_string($record->request_body) ? $record->request_body : json_encode($record->request_body);
                                $bodyJson = json_decode($bodyStr, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($bodyJson)) {
                                    $request->withBody($bodyStr, 'application/json');
                                } else {
                                    $request->withBody($bodyStr, 'text/plain');
                                }
                            }

                            $method = strtolower($record->method ?? 'get');
                            $response = $request->$method($record->url);

                            Notification::make()
                                ->title("Request completed with status {$response->status()}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Request failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }
                }),
        ];
    }
}
