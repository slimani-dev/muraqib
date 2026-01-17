<?php

namespace App\Filament\Resources\Portainers\Tables;

use App\Models\Portainer;
use App\Services\PortainerService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PortainersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('url')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('status')
                    ->badge(),

                TextColumn::make('version')
                    ->toggleable()
                    ->placeholder('Not synced')
                    ->icon('heroicon-o-information-circle')
                    ->description(fn (Portainer $record) => $record->latest_version && version_compare($record->latest_version, ltrim($record->version ?? '', 'v'), '>')
                        ? "Update Available: {$record->latest_version}"
                        : null
                    )
                    ->color(fn (Portainer $record) => $record->latest_version && version_compare($record->latest_version, ltrim($record->version ?? '', 'v'), '>')
                        ? 'warning'
                        : null
                    ),

                TextColumn::make('last_synced_at')
                    ->dateTime()
                    ->since()
                    ->toggleable()
                    ->placeholder('Never'),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('sync')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Portainer $record) {
                        $service = new PortainerService($record);
                        $results = $service->sync();

                        if (! $results['connected']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Connection Failed')
                                ->body('Could not connect to Portainer API.')
                                ->danger()
                                ->send();

                            return;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Sync completed')
                            ->success()
                            ->send();
                    }),

                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
