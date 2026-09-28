<?php

namespace App\Filament\Resources\MediaServices\Tables;

use App\Enums\MediaServiceStatus;
use App\Models\MediaService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

class MediaServicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('type')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('url')
                    ->label('Service URL')
                    ->limit(40),

                TextColumn::make('status_check')
                    ->label('Status check')
                    ->state(fn (MediaService $record): string => match (true) {
                        $record->statusCheckUrl() === null => 'None (no public URL)',
                        $record->statusCheckRunsOnServer() => 'Server: '.(MediaServiceStatus::tryFrom((string) Cache::get($record->statusCacheKey()))?->getLabel() ?? 'not checked yet'),
                        default => 'Browser',
                    })
                    ->description(fn (MediaService $record): ?string => $record->statusCheckUrl())
                    ->color('gray'),

                ToggleColumn::make('is_enabled')
                    ->label('Enabled'),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->since()
                    ->toggleable(),
            ])
            ->recordActions([
                Action::make('testConnection')
                    ->label('Test')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (MediaService $record): void {
                        $apiWorks = $record->client()->checkConnection();

                        Notification::make()
                            ->title($apiWorks ? "{$record->name} API connected" : "{$record->name} API failed")
                            ->{$apiWorks ? 'success' : 'danger'}()
                            ->send();
                    }),

                EditAction::make(),

                DeleteAction::make(),
            ]);
    }
}
