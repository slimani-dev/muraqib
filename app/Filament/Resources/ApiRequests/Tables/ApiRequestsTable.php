<?php

namespace App\Filament\Resources\ApiRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ApiRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
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
                    ->limit(50)
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
                ViewAction::make(),
                EditAction::make(),
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
