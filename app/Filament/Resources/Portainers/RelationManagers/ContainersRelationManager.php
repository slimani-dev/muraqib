<?php

namespace App\Filament\Resources\Portainers\RelationManagers;

use App\Models\Container;
use App\Services\PortainerService;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ContainersRelationManager extends RelationManager
{
    protected static string $relationship = 'containers';

    protected static ?string $title = 'Containers';

    public function isReadOnly(): bool
    {
        return true;
    }

    protected ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Sync data from API before querying
                // $this->syncContainersFromApi();

                return $query;
            })
            //->poll('10s')
            ->columns([
                Tables\Columns\ImageColumn::make('icon')
                    ->label('')
                    ->imageSize('auto')
                    ->imageHeight(30)
                    ->inline()
                    ->extraAttributes([
                        'class' => 'mx-auto aspect-square flex item-center justify-center p-0',
                    ]),

                Tables\Columns\TextColumn::make('name')
                    ->label('Container Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn($record) => $record->display_name ? 'ID: ' . substr($record->container_id, 0, 12) : null)
                    ->formatStateUsing(fn($record) => $record->display_name ?? $record->name),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
                    ->tooltip(fn($record) => $record->description)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('image')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn($record) => $record->image),

                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'running' => 'success',
                        'exited' => 'danger',
                        'paused' => 'warning',
                        'restarting' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('stack_name')
                    ->label('Stack')
                    ->badge()
                    ->color('info')
                    ->placeholder('No stack'),

                Tables\Columns\TextColumn::make('status')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->status),

                Tables\Columns\TextColumn::make('created_at_portainer')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),

            ])
            ->filters([
                /*Tables\Filters\SelectFilter::make('stack_name')
                    ->label('Stack')
                    ->options(fn(ContainersRelationManager $livewire) => Container::where('portainer_id', $livewire->getOwnerRecord()->id)->distinct()->whereNotNull('stack_name')->pluck('stack_name', 'stack_name')->toArray())
                    ->searchable(),

                Tables\Filters\TernaryFilter::make('important_only')
                    ->label('Filter')
                    ->placeholder('All Containers')
                    ->trueLabel('Important Only')
                    ->falseLabel('Others')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('display_name')->orWhereNotNull('icon'),
                        false: fn(Builder $query) => $query->whereNull('display_name')->whereNull('icon'),
                    ),
                Tables\Filters\SelectFilter::make('endpoint_name')
                    ->label('Endpoint')
                    ->options(fn(ContainersRelationManager $livewire) => Container::where('portainer_id', $livewire->getOwnerRecord()->id)->distinct()->whereNotNull('endpoint_name')->pluck('endpoint_name', 'endpoint_name')->toArray())
                    ->searchable(),*/
            ])
            ->headerActions([
                // Sync action removed
            ])
            ->recordActions([
                Action::make('open_url')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn(Container $record) => $record->url, shouldOpenInNewTab: true)
                    ->visible(fn(Container $record) => !empty($record->url)),
            ])
            ->toolbarActions([
                //
            ]);
    }

    // Local sync method removed permanently


    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Containers';
    }
}
