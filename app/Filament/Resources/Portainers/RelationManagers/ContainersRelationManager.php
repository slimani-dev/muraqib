<?php

namespace App\Filament\Resources\Portainers\RelationManagers;

use App\Enums\UpdateStatus;
use App\Models\Container;
use App\Services\RegistryApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

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
            // ->poll('10s')
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
                    ->copyable()
                    ->copyableState(fn (Container $record) => $record->name)
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (Container $record) => $record->display_name ? $record->name : null)
                    ->formatStateUsing(fn (Container $record) => $record->display_name ?? $record->name),

                Tables\Columns\TextColumn::make('container_id')
                    ->label('Container ID')
                    ->limit(12)
                    ->copyable()
                    ->copyableState(fn (Container $record) => $record->container_id)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('image_digest')
                    ->label('Digest')
                    ->formatStateUsing(function (?string $state): string {
                        if (! $state) {
                            return '—';
                        }

                        // Strip "sha256:" prefix, show first 12 chars
                        $sha = str_starts_with($state, 'sha256:') ? substr($state, 7) : $state;

                        return substr($sha, 0, 12);
                    })
                    ->copyable()
                    ->copyableState(fn (Container $record) => $record->image_digest)
                    ->tooltip(fn (Container $record) => $record->image_digest)
                    ->searchable()
                    ->fontFamily('mono')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->tooltip(fn ($record) => $record->description)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('image')
                    ->limit(30)
                    ->copyable()
                    ->copyableState(fn (Container $record) => $record->image)
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('update_status')
                    ->label('Update')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
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
                    ->tooltip(fn ($record) => $record->status),

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
                ViewAction::make()->slideOver(),

                Action::make('check_update')
                    ->label('Check Update')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->visible(fn (Container $record) => ! empty($record->image_digest))
                    ->action(function (Container $record, RegistryApiService $registryApi) {
                        $result = $registryApi->checkDigest($record->image, $record->image_digest);

                        if (! $result || isset($result['error']) || ! isset($result['is_latest'])) {
                            $record->update([
                                'update_status' => UpdateStatus::Error,
                                'update_checked_at' => now(),
                                'update_error' => $result['error'] ?? 'Could not reach registry API',
                            ]);

                            Notification::make()
                                ->title('Update Check Failed')
                                ->body($result['error'] ?? 'Could not reach registry API')
                                ->danger()
                                ->send();

                            return;
                        }

                        if ($result['is_latest']) {
                            $record->update([
                                'update_status' => UpdateStatus::UpToDate,
                                'update_checked_at' => now(),
                                'current_release' => $result['current_tag'] ?? null,
                                'available_tags' => $result['relevant_tags'] ?? null,
                                'latest_digest' => $result['latest_digest'] ?? null,
                                'update_error' => null,
                            ]);

                            Notification::make()
                                ->title('Image is Up to Date')
                                ->success()
                                ->send();
                        } else {
                            $record->update([
                                'update_status' => UpdateStatus::UpdateAvailable,
                                'update_checked_at' => now(),
                                'current_release' => $result['current_tag'] ?? null,
                                'available_tags' => $result['relevant_tags'] ?? null,
                                'latest_digest' => $result['latest_digest'] ?? null,
                                'update_error' => null,
                            ]);

                            Notification::make()
                                ->title('Update Available')
                                ->body("A newer image exists: {$result['latest_tag']}")
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('open_url')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Container $record) => $record->url, shouldOpenInNewTab: true)
                    ->visible(fn (Container $record) => ! empty($record->url)),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('check_updates')
                        ->label('Check Updates')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function (Collection $records, RegistryApiService $registryApi) {
                            $checked = 0;
                            $updatesFound = 0;

                            foreach ($records as $record) {
                                if (empty($record->image_digest)) {
                                    continue;
                                }

                                $result = $registryApi->checkDigest($record->image, $record->image_digest);

                                if (! $result || isset($result['error']) || ! isset($result['is_latest'])) {
                                    $record->update([
                                        'update_status' => UpdateStatus::Error,
                                        'update_checked_at' => now(),
                                        'update_error' => $result['error'] ?? 'Could not reach registry API',
                                    ]);

                                    continue;
                                }

                                if ($result['is_latest']) {
                                    $record->update([
                                        'update_status' => UpdateStatus::UpToDate,
                                        'update_checked_at' => now(),
                                        'current_release' => $result['current_tag'] ?? null,
                                        'available_tags' => $result['relevant_tags'] ?? null,
                                        'latest_digest' => $result['latest_digest'] ?? null,
                                        'update_error' => null,
                                    ]);
                                } else {
                                    $record->update([
                                        'update_status' => UpdateStatus::UpdateAvailable,
                                        'update_checked_at' => now(),
                                        'current_release' => $result['current_tag'] ?? null,
                                        'available_tags' => $result['relevant_tags'] ?? null,
                                        'latest_digest' => $result['latest_digest'] ?? null,
                                        'update_error' => null,
                                    ]);
                                    $updatesFound++;
                                }

                                $checked++;
                            }

                            if ($checked === 0) {
                                Notification::make()
                                    ->title('No containers to check')
                                    ->body('Selected containers do not have image digests')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $message = "Checked {$checked} containers.";
                            if ($updatesFound > 0) {
                                $message .= " Found {$updatesFound} updates available.";
                                Notification::make()->title('Update Check Complete')->body($message)->warning()->send();
                            } else {
                                Notification::make()->title('All Images Up to Date')->body($message)->success()->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    // Local sync method removed permanently

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('General Information')
                    ->schema([
                        TextEntry::make('name')
                            ->weight('bold')
                            ->formatStateUsing(fn (Container $record) => $record->display_name ?? $record->name),
                        TextEntry::make('current_release')
                            ->label('Release')
                            ->placeholder('Unknown'),
                        TextEntry::make('container_id')
                            ->label('Container ID')
                            ->copyable()
                            ->fontFamily('mono'),
                        TextEntry::make('state')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'running' => 'success',
                                'exited' => 'danger',
                                'paused' => 'warning',
                                'restarting' => 'info',
                                default => 'gray',
                            }),
                        TextEntry::make('status'),
                        TextEntry::make('portainer.name')
                            ->label('Portainer Server'),
                        TextEntry::make('endpoint_name')
                            ->label('Endpoint'),
                    ])
                    ->columns(2),

                Section::make('Image Information')
                    ->schema([
                        ImageEntry::make('icon')
                            ->hiddenLabel()
                            ->extraAttributes(['class' => 'mix-blend-multiply']),
                        TextEntry::make('image')
                            ->columnSpanFull()
                            ->copyable(),
                        TextEntry::make('image_id')
                            ->label('Image ID')
                            ->fontFamily('mono')
                            ->copyable()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '—';
                                }
                                $sha = str_starts_with($state, 'sha256:') ? substr($state, 7) : $state;

                                return substr($sha, 0, 12);
                            }),
                        TextEntry::make('image_digest')
                            ->label('Digest')
                            ->fontFamily('mono')
                            ->copyable()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '—';
                                }
                                $sha = str_starts_with($state, 'sha256:') ? substr($state, 7) : $state;

                                return substr($sha, 0, 12);
                            }),
                    ])
                    ->columns(2),

                Section::make('Update Information')
                    ->schema([
                        TextEntry::make('update_status')
                            ->badge(),
                        TextEntry::make('update_checked_at')
                            ->label('Last Checked')
                            ->dateTime(),
                        TextEntry::make('current_release')
                            ->label('Current Release')
                            ->placeholder('—'),
                        TextEntry::make('latest_digest')
                            ->label('Latest Digest')
                            ->fontFamily('mono')
                            ->copyable()
                            ->formatStateUsing(function (?string $state): string {
                                if (! $state) {
                                    return '—';
                                }
                                $sha = str_starts_with($state, 'sha256:') ? substr($state, 7) : $state;

                                return substr($sha, 0, 12);
                            })
                            ->placeholder('—')
                            ->visible(fn (Container $record) => $record->update_status !== UpdateStatus::Error),
                        TextEntry::make('available_tags')
                            ->label('Available Tags')
                            ->badge()
                            ->copyable()
                            ->columnSpanFull()
                            ->placeholder('Run update check to fetch tags')
                            ->visible(fn (Container $record) => ! empty($record->available_tags)),
                        TextEntry::make('update_error')
                            ->label('Check Error')
                            ->color('danger')
                            ->columnSpanFull()
                            ->visible(fn (Container $record) => ! empty($record->update_error)),
                    ])
                    ->columns(2),

                Section::make('Stack & Other Details')
                    ->schema([
                        TextEntry::make('stack_name')
                            ->badge()
                            ->color('info')
                            ->placeholder('No stack'),
                        TextEntry::make('created_at_portainer')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('url')
                            ->url(fn (Container $record) => $record->url)
                            ->openUrlInNewTab()
                            ->placeholder('—'),
                        TextEntry::make('description')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Containers';
    }
}
