<?php

namespace App\Filament\Resources\ApiRequests\Tables;

use App\Models\ApiRequest;
use App\Services\ApiMonitor\LoggingMiddleware;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class ApiRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('5s')
            ->columns([
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Time'),
                TextColumn::make('service')
                    ->badge()
                    ->searchable(),
                TextColumn::make('method')
                    ->badge()
                    ->color(fn (string $state): string => match (strtoupper($state)) {
                        'GET' => 'info',
                        'POST' => 'success',
                        'PUT', 'PATCH' => 'warning',
                        'DELETE' => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                TextColumn::make('status_code')
                    ->badge()
                    ->color(fn ($state): string => match (true) {
                        $state >= 200 && $state < 300 => 'success',
                        $state >= 300 && $state < 400 => 'info',
                        $state >= 400 && $state < 500 => 'warning',
                        $state >= 500 => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('url')
                    ->limit(100)
                    ->tooltip(fn ($state) => $state)
                    ->searchable(),
                TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Action')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('user_id') // Avoid N+1 relation if not eager loaded, or use user.name with eager load
                    ->label('User')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->slideOver()
                    ->extraModalFooterActions([
                        Action::make('copy_all')
                            ->label('Copy All Details')
                            ->icon('heroicon-o-clipboard-document')
                            ->color('gray')
                            ->action(function (ApiRequest $record, Component $livewire) {
                                $data = [
                                    'request' => [
                                        'url' => $record->url,
                                        'method' => $record->method,
                                        'headers' => $record->request_headers,
                                        'body' => is_string($record->request_body) ? json_decode($record->request_body) : $record->request_body,
                                    ],
                                    'response' => [
                                        'status' => $record->status_code,
                                        'duration' => $record->duration_ms,
                                        'headers' => $record->response_headers,
                                        'body' => is_string($record->response_body) ? json_decode($record->response_body) : $record->response_body,
                                    ],
                                ];

                                $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                                // Ensure strict safety against JS injection
                                $encoded = base64_encode($json);

                                $livewire->js("window.navigator.clipboard.writeText(atob('{$encoded}'));");

                                Notification::make()
                                    ->title('Copied to clipboard')
                                    ->success()
                                    ->send();
                            }),
                        ReplicateAction::make('rerun')
                            ->label('Rerun')
                            ->icon('heroicon-o-arrow-path')
                            ->color('warning')
                            ->excludeAttributes(['id', 'status_code', 'response_headers', 'response_body', 'duration_ms', 'error', 'user_id', 'ip_address', 'meta'])
                            ->mutateRecordDataUsing(function (array $data): array {
                                $headers = [];
                                if (isset($data['request_headers']) && is_array($data['request_headers'])) {
                                    foreach ($data['request_headers'] as $key => $values) {
                                        $headers[$key] = is_array($values) ? implode(', ', $values) : $values;
                                    }
                                }

                                $body = is_string($data['request_body'] ?? null) ? $data['request_body'] : (is_null($data['request_body'] ?? null) ? null : json_encode($data['request_body'], JSON_PRETTY_PRINT));

                                $data['request_headers'] = $headers;
                                $data['request_body'] = $body;
                                $data['service'] = $data['service'] ?? 'API Runner';
                                $data['method'] = strtoupper($data['method'] ?? 'GET');

                                return $data;
                            })
                            ->extraModalFooterActions(fn (ReplicateAction $action): array => [
                                $action->makeModalSubmitAction('run', arguments: ['run' => true])
                                    ->label('Create & Run')
                                    ->icon('heroicon-o-paper-airplane')
                                    ->color('warning'),
                            ])
                            ->after(function (ApiRequest $replica, array $arguments) {
                                if ($arguments['run'] ?? false) {
                                    try {
                                        $request = Http::withMiddleware(
                                            new LoggingMiddleware($replica->service ?? 'API Runner', 'Rerun')
                                        );

                                        if (! empty($replica->request_headers)) {
                                            $request->withHeaders($replica->request_headers);
                                        }

                                        if (! empty($replica->request_body)) {
                                            $bodyStr = is_string($replica->request_body) ? $replica->request_body : json_encode($replica->request_body);
                                            $bodyJson = json_decode($bodyStr, true);
                                            if (json_last_error() === JSON_ERROR_NONE && is_array($bodyJson)) {
                                                $request->withBody($bodyStr, 'application/json');
                                            } else {
                                                $request->withBody($bodyStr, 'text/plain');
                                            }
                                        }

                                        $method = strtolower($replica->method ?? 'get');
                                        $response = $request->$method($replica->url);

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
                    ]),
                // EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
