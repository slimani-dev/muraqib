<?php

namespace App\Filament\Resources\Repositories\Tables;

use App\Models\Repository;
use App\Services\Git\RepositorySyncer;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class RepositoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Repository')
                    ->state(fn (Repository $record): string => $record->title())
                    ->description(fn (Repository $record): string => $record->fullName())
                    ->icon(fn (Repository $record) => $record->account->provider->getIcon())
                    ->url(fn (Repository $record): ?string => $record->html_url, shouldOpenInNewTab: true)
                    ->searchable(['name', 'owner', 'display_name'])
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('account.name')
                    ->label('Account')
                    ->toggleable(),

                TextColumn::make('stars')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('open_pull_requests')
                    ->label('Open PRs')
                    ->numeric()
                    ->placeholder('—'),

                TextColumn::make('latest_release.tag')
                    ->label('Release')
                    ->placeholder('—'),

                TextColumn::make('ci.status')
                    ->label('CI')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'success' => 'success',
                        'failure' => 'danger',
                        'running' => 'info',
                        default => 'gray',
                    })
                    ->placeholder('—'),

                ToggleColumn::make('is_managed')
                    ->label('Managed'),

                TextColumn::make('synced_at')
                    ->label('Synced')
                    ->since()
                    ->placeholder('Not yet')
                    ->description(fn (Repository $record): ?string => $record->sync_error ? 'Last sync failed' : null)
                    ->tooltip(fn (Repository $record): ?string => $record->sync_error)
                    ->color(fn (Repository $record): ?string => $record->sync_error ? 'danger' : null),
            ])
            ->recordActions([
                Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Repository $record, RepositorySyncer $syncer): void {
                        $syncer->sync($record);

                        $record->sync_error
                            ? Notification::make()->title('Sync failed')->body($record->sync_error)->danger()->send()
                            : Notification::make()->title("{$record->title()} synced")->success()->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
