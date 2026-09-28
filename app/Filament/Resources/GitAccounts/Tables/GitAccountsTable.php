<?php

namespace App\Filament\Resources\GitAccounts\Tables;

use App\Models\GitAccount;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GitAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('provider')
                    ->badge()
                    ->sortable(),

                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),

                TextColumn::make('base_url')
                    ->label('Instance')
                    ->state(fn (GitAccount $record): string => $record->webUrl()),

                TextColumn::make('repositories_count')
                    ->label('Repositories')
                    ->counts('repositories'),

                TextColumn::make('token')
                    ->label('Token')
                    ->state(fn (GitAccount $record): string => filled($record->token) ? 'Set' : 'None')
                    ->color(fn (GitAccount $record): string => filled($record->token) ? 'success' : 'gray')
                    ->badge(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
