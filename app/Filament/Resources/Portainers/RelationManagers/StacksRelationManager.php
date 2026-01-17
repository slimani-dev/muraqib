<?php

namespace App\Filament\Resources\Portainers\RelationManagers;

use App\Models\Portainer;
use App\Models\Stack;
use App\Services\PortainerService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Riodwanto\FilamentAceEditor\AceEditor;

class StacksRelationManager extends RelationManager
{
    protected static string $relationship = 'stacks';

    protected static ?string $title = 'Stacks';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                AceEditor::make('stack_file_content')
                    ->label('Docker Compose')
                    ->mode('yaml')
                    ->theme('github')
                    ->darkTheme('dracula')
                    ->addExtensions(['language_tools', 'beautify'])
                    ->tabSize(4)
                    ->required(),


                Forms\Components\Repeater::make('env')
                    ->table([
                        Forms\Components\Repeater\TableColumn::make('Name')
                            ->hiddenHeaderLabel(),
                        Forms\Components\Repeater\TableColumn::make('Value')
                            ->hiddenHeaderLabel(),
                    ])
                    ->extraAttributes([
                        'class' => 'no-header'
                    ])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->placeholder('Key'),
                        Forms\Components\TextInput::make('value')
                            ->required()
                            ->placeholder('Value'),
                    ])
                    ->columns(2)
                    ->reorderableWithDragAndDrop(false)
                    ->addActionLabel('Add Environment Variable'),
            ]);

    }

    protected ?string $pollingInterval = '10s';

    public function table(Table $table): Table
    {
        /** @var Portainer $portainer */
        $portainer = $this->getOwnerRecord();

        return $table
            ->modifyQueryUsing(function (Builder $query) {
                // Sync data from API before querying
                // $this->syncStacksFromApi(force: true);

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
                    ->label('Stack Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Stack $record) => "Endpoint #{$record->endpoint_id}"),

                Tables\Columns\TextColumn::make('stack_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => 'Running',
                        2 => 'Stopped',
                        default => 'Unknown',
                    })
                    ->color(fn($state) => match ($state) {
                        1 => 'success',
                        2 => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn($state) => match ($state) {
                        1 => 'heroicon-o-play-circle',
                        2 => 'heroicon-o-stop-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('stack_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        1 => 'Swarm',
                        2 => 'Compose',
                        default => 'Unknown',
                    })
                    ->color('info'),

                Tables\Columns\TextColumn::make('created_at_portainer')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn(Stack $record) => $record->created_at_portainer?->format('M d, Y g:i A')),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('stack_status')
                    ->label('Status')
                    ->options([
                        1 => 'Running',
                        2 => 'Stopped',
                    ]),
            ])
            ->headerActions([
                // Sync action removed
            ])
            ->recordActions([

                Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn(Stack $record) => $record->stack_status === 2)
                    ->requiresConfirmation()
                    ->modalHeading(fn(Stack $record) => "Start stack '{$record->name}'?")
                    ->modalDescription('This will start all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->startStack((int)$record->external_id, $record->endpoint_id);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Stack started')
                                ->body("Stack '{$record->name}' has been started successfully.")
                                ->send();

                            // Trigger re-sync
                            $this->syncStacksFromApi(force: true);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to start stack')
                                ->body("Could not start stack '{$record->name}'. Check Portainer logs.")
                                ->send();
                        }
                    }),

                Action::make('restart')
                    ->label('Restart')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn(Stack $record) => $record->stack_status === 1)
                    ->requiresConfirmation()
                    ->modalHeading(fn(Stack $record) => "Restart stack '{$record->name}'?")
                    ->modalDescription('This will stop and start all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->restartStack((int)$record->external_id, $record->endpoint_id);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Stack restarted')
                                ->body("Stack '{$record->name}' has been restarted successfully.")
                                ->send();

                            $this->syncStacksFromApi(force: true);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to restart stack')
                                ->body("Could not restart stack '{$record->name}'. Check Portainer logs.")
                                ->send();
                        }
                    }),

                Action::make('stop')
                    ->label('Stop')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn(Stack $record) => $record->stack_status === 1)
                    ->requiresConfirmation()
                    ->modalHeading(fn(Stack $record) => "Stop stack '{$record->name}'?")
                    ->modalDescription('This will stop all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->stopStack((int)$record->external_id, $record->endpoint_id);

                        if ($success) {
                            Notification::make()
                                ->success()
                                ->title('Stack stopped')
                                ->body("Stack '{$record->name}' has been stopped successfully.")
                                ->send();

                            $this->syncStacksFromApi(force: true);
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to stop stack')
                                ->body("Could not stop stack '{$record->name}'. Check Portainer logs.")
                                ->send();
                        }
                    }),

                EditAction::make()
                ->modalWidth(Width::FiveExtraLarge)
            ])
            ->toolbarActions([
                //
            ]);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function syncStacksFromApi(bool $force = false): void
    {
        /** @var Portainer $portainer */
        $portainer = $this->getOwnerRecord();

        // Redis cache key for this portainer's last sync
        $cacheKey = "portainer:{$portainer->id}:stacks:last_sync";
        $cacheDuration = 300; // 5 minutes in seconds

        // Check if we need to sync (unless forced)
        if (!$force) {
            $lastSync = cache()->get($cacheKey);
            if ($lastSync && now()->diffInSeconds($lastSync) < $cacheDuration) {
                // Cache still valid, skip sync
                return;
            }
        }

        $service = new PortainerService($portainer);
        $apiStacks = $service->getStacks();
        $apiContainers = $service->getContainers();

        // Delete stacks that no longer exist in API
        $apiStackIds = $apiStacks->pluck('id')->toArray();
        Stack::where('portainer_id', $portainer->id)
            ->whereNotIn('external_id', $apiStackIds)
            ->delete();

        // Upsert stacks from API
        foreach ($apiStacks as $stackData) {
            $icon = null;

            // If stack is stopped, try to get icon from docker-compose file
            if ($stackData->status === 2) {
                $stackFile = $service->getStackFile((int)$stackData->id);

                // DEBUG LOGGING
                if ($stackData->id == 10) {
                    \Illuminate\Support\Facades\Log::info('Debugging Stack 10 (Kavita): File fetched? ' . ($stackFile ? 'Yes' : 'No') . ' Length: ' . strlen($stackFile ?? ''));
                }

                if ($stackFile) {
                    try {
                        $compose = \Symfony\Component\Yaml\Yaml::parse($stackFile);

                        if ($stackData->id == 10) {
                            \Illuminate\Support\Facades\Log::info('Debugging Stack 10: YAML Parsed. Services: ' . json_encode(array_keys($compose['services'] ?? [])));
                        }

                        // Look for muraqib.icon or glance.icon in service labels
                        if (isset($compose['services']) && is_array($compose['services'])) {
                            // First, try to find main service
                            foreach ($compose['services'] as $serviceName => $serviceData) {
                                if (isset($serviceData['labels'])) {
                                    $labels = $this->normalizeLabels($serviceData['labels']);

                                    if ($stackData->id == 10) {
                                        \Illuminate\Support\Facades\Log::info("Debugging Stack 10: Service $serviceName labels: " . json_encode($labels));
                                    }

                                    // Check if this is main service
                                    if (isset($labels['muraqib.main']) && $labels['muraqib.main'] === 'true') {
                                        $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
                                        break;
                                    }
                                }
                            }

                            // If no main service with icon, find any service with icon
                            if (!$icon) {
                                foreach ($compose['services'] as $serviceData) {
                                    if (isset($serviceData['labels'])) {
                                        $labels = $this->normalizeLabels($serviceData['labels']);
                                        $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
                                        if ($icon) {
                                            break;
                                        }
                                    }
                                }
                            }

                            if ($stackData->id == 10) {
                                \Illuminate\Support\Facades\Log::info('Debugging Stack 10: Final Icon Extracted: ' . var_export($icon, true));
                            }
                        }
                    } catch (\Exception $e) {
                        if ($stackData->id == 10) {
                            \Illuminate\Support\Facades\Log::error('Debugging Stack 10: YAML Parse Error: ' . $e->getMessage());
                        }
                    }
                }
            } else {
                // Stack is running, extract icon from running containers
                $stackContainers = $apiContainers->filter(function ($container) use ($stackData) {
                    return ($container->labels['com.docker.compose.project'] ?? '') === $stackData->name;
                });

                // First, try to find main container with icon
                $mainContainer = $stackContainers->first(function ($container) {
                    return ($container->labels['muraqib.main'] ?? 'false') === 'true';
                });

                if ($mainContainer) {
                    $icon = $mainContainer->labels['muraqib.icon']
                        ?? $mainContainer->labels['glance.icon']
                        ?? null;
                }

                // If no icon from main container, find first container with an icon
                if (!$icon) {
                    $containerWithIcon = $stackContainers->first(function ($container) {
                        return isset($container->labels['muraqib.icon']) || isset($container->labels['glance.icon']);
                    });

                    if ($containerWithIcon) {
                        $icon = $containerWithIcon->labels['muraqib.icon']
                            ?? $containerWithIcon->labels['glance.icon']
                            ?? null;
                    }
                }
            }

            Stack::updateOrCreate(
                [
                    'portainer_id' => $portainer->id,
                    'external_id' => (string)$stackData->id,
                ],
                [
                    'name' => $stackData->name,
                    'endpoint_id' => $stackData->endpointId,
                    'stack_status' => $stackData->status,
                    'stack_type' => $stackData->type,
                    'icon' => $icon,
                    'created_at_portainer' => $stackData->createdAt,
                ]
            );
        }

        // Update cache timestamp
        cache()->put($cacheKey, now(), $cacheDuration * 2); // Store for 10 min (2x duration)
    }

    /**
     * Normalize labels from docker-compose (can be array of strings or object)
     */
    protected function normalizeLabels(array $labels): array
    {
        $normalized = [];

        // Check if associative array (map) or indexed array (list)
        $isList = array_key_exists(0, $labels);

        if ($isList) {
            foreach ($labels as $label) {
                if (is_string($label) && str_contains($label, '=')) {
                    [$key, $value] = explode('=', $label, 2);
                    $normalized[trim($key)] = trim($value);
                }
            }
        } else {
            $normalized = $labels;
        }

        return $normalized;
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return 'Stacks';
    }
}
