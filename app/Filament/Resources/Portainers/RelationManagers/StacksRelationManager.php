<?php

namespace App\Filament\Resources\Portainers\RelationManagers;

use App\Models\Portainer;
use App\Models\Stack;
use App\Services\PortainerService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
                        'class' => 'no-header',
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

                Section::make('Deployment Options')
                    ->schema([
                        Toggle::make('redeploy')
                            ->label('Redeploy Stack')
                            ->default(true)
                            ->live()
                            ->columnSpanFull()
                            ->helperText('Update the stack definition and redeploy service.'),

                        Checkbox::make('prune')
                            ->label('Prune services')
                            ->helperText('Remove services that are no longer referenced in the stack definition.')
                            ->visible(fn ($get) => $get('redeploy')),

                        Checkbox::make('pull_image')
                            ->label('Pull latest image')
                            ->helperText('Force a pull of the image even if it exists locally.')
                            ->visible(fn ($get) => $get('redeploy')),

                        \CodeWithDennis\SimpleAlert\Components\SimpleAlert::make('deploy_warning')
                            ->title('Deployment in progress')
                            ->description('This operation may take a few moments to complete, especially when pulling new images or pruning services. Please wait for the process to finish.')
                            ->warning()
                            ->columnSpanFull()
                            ->icon('heroicon-o-clock')
                            ->visible(fn ($get) => $get('redeploy')),
                    ])
                    ->columns(2)
                    ->visible(fn ($operation) => $operation === 'edit'),
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
                    ->description(fn (Stack $record) => "Endpoint #{$record->endpoint_id}"),

                Tables\Columns\TextColumn::make('stack_status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        1 => 'Running',
                        2 => 'Stopped',
                        default => 'Unknown',
                    })
                    ->color(fn ($state) => match ($state) {
                        1 => 'success',
                        2 => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        1 => 'heroicon-o-play-circle',
                        2 => 'heroicon-o-stop-circle',
                        default => 'heroicon-o-question-mark-circle',
                    }),

                Tables\Columns\TextColumn::make('stack_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
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
                    ->description(fn (Stack $record) => $record->created_at_portainer?->format('M d, Y g:i A')),

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
                    ->visible(fn (Stack $record) => $record->stack_status === 2)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Stack $record) => "Start stack '{$record->name}'?")
                    ->modalDescription('This will start all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->startStack((int) $record->external_id, $record->endpoint_id);

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
                    ->visible(fn (Stack $record) => $record->stack_status === 1)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Stack $record) => "Restart stack '{$record->name}'?")
                    ->modalDescription('This will stop and start all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->restartStack((int) $record->external_id, $record->endpoint_id);

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
                    ->visible(fn (Stack $record) => $record->stack_status === 1)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Stack $record) => "Stop stack '{$record->name}'?")
                    ->modalDescription('This will stop all containers in the stack.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->stopStack((int) $record->external_id, $record->endpoint_id);

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
                    ->mountUsing(function ($form, $record) use ($portainer) {
                        // 1. Sync latest data from Portainer
                        $service = new PortainerService($portainer);
                        $apiStack = $service->getStack((int) $record->external_id);
                        $stackFile = $service->getStackFile((int) $record->external_id);

                        if ($apiStack) {
                            // Update local record
                            $env = [];
                            if ($apiStack->env) {
                                foreach ($apiStack->env as $envVar) {
                                    if (is_array($envVar) && isset($envVar['name'], $envVar['value'])) {
                                        $env[] = $envVar;
                                    } elseif (is_string($envVar) && str_contains($envVar, '=')) {
                                        [$key, $val] = explode('=', $envVar, 2);
                                        $env[] = ['name' => $key, 'value' => $val];
                                    }
                                }
                            }

                            $record->update([
                                'stack_file_content' => $stackFile ?? $record->stack_file_content,
                                'env' => $env,
                            ]);
                        }

                        // 2. Fill form with fresh data
                        $form->fill([
                            'stack_file_content' => $record->stack_file_content,
                            'env' => $record->env,
                            'redeploy' => true,
                            'prune' => false,
                            'pull_image' => false,
                        ]);
                    })
                    ->action(function (Stack $record, array $data) use ($portainer) {
                        $service = new PortainerService($portainer);

                        // normalize env
                        $env = [];
                        foreach ($data['env'] ?? [] as $item) {
                            $env[] = ['name' => $item['name'], 'value' => $item['value']];
                        }

                        $success = $service->updateStack(
                            (int) $record->external_id,
                            $record->endpoint_id,
                            $data['stack_file_content'],
                            $env,
                            $data['prune'] ?? false,
                            $data['pull_image'] ?? false
                        );

                        if ($success) {
                            // Wait for Portainer to apply changes (deployment can take time)
                            sleep(4);

                            // Re-sync after update to ensure DB is consistent (e.g. status changes)
                            $this->syncStacksFromApi(force: true);

                            Notification::make()
                                ->success()
                                ->title('Stack updated')
                                ->body("Stack '{$record->name}' has been updated successfully.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to update stack')
                                ->body("Could not update stack '{$record->name}'. Check Portainer logs.")
                                ->send();

                            // Halt execution to prevent modal closing?
                            // Filament Action `action` doesn't automatically halt on return.
                            // We might want to `halt()` or throw exception if we want modal to stay open.
                            // But for now notification is enough.
                        }
                    }),

                Action::make('delete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn (Stack $record) => $record->stack_status === 2)
                    ->requiresConfirmation()
                    ->modalHeading(fn (Stack $record) => "Delete stack '{$record->name}'?")
                    ->modalDescription('This will permanently delete the stack from Portainer. The stack must be stopped first.')
                    ->action(function (Stack $record) use ($portainer) {
                        $service = new PortainerService($portainer);

                        $success = $service->deleteStack((int) $record->external_id, $record->endpoint_id);

                        if ($success) {
                            $record->delete();

                            Notification::make()
                                ->success()
                                ->title('Stack deleted')
                                ->body("Stack '{$record->name}' has been deleted successfully.")
                                ->send();
                        } else {
                            Notification::make()
                                ->danger()
                                ->title('Failed to delete stack')
                                ->body("Could not delete stack '{$record->name}'. Check Portainer logs.")
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                // No Bulk Delete
                // Tables\Actions\DeleteBulkAction::make(),

                \Filament\Actions\BulkAction::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($portainer) {
                        $service = new PortainerService($portainer);
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->stack_status === 2) { // Only start stopped stacks
                                if ($service->startStack((int) $record->external_id, $record->endpoint_id)) {
                                    $count++;
                                }
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title("Started {$count} stacks")
                            ->send();

                        // No easy way to sync just these, might as well full sync if needed or rely on poll
                    }),

                \Filament\Actions\BulkAction::make('stop')
                    ->label('Stop')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($portainer) {
                        $service = new PortainerService($portainer);
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->stack_status === 1) { // Only stop running stacks
                                if ($service->stopStack((int) $record->external_id, $record->endpoint_id)) {
                                    $count++;
                                }
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title("Stopped {$count} stacks")
                            ->send();
                    }),

                \Filament\Actions\BulkAction::make('restart')
                    ->label('Restart')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function (Collection $records) use ($portainer) {
                        $service = new PortainerService($portainer);
                        $count = 0;
                        foreach ($records as $record) {
                            if ($record->stack_status === 1) { // Only restart running stacks
                                if ($service->restartStack((int) $record->external_id, $record->endpoint_id)) {
                                    $count++;
                                }
                            }
                        }

                        Notification::make()
                            ->success()
                            ->title("Restarted {$count} stacks")
                            ->send();
                    }),
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
        if (! $force) {
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
                $stackFile = $service->getStackFile((int) $stackData->id);

                // DEBUG LOGGING
                if ($stackData->id == 10) {
                    \Illuminate\Support\Facades\Log::info('Debugging Stack 10 (Kavita): File fetched? '.($stackFile ? 'Yes' : 'No').' Length: '.strlen($stackFile ?? ''));
                }

                if ($stackFile) {
                    try {
                        $compose = \Symfony\Component\Yaml\Yaml::parse($stackFile);

                        if ($stackData->id == 10) {
                            \Illuminate\Support\Facades\Log::info('Debugging Stack 10: YAML Parsed. Services: '.json_encode(array_keys($compose['services'] ?? [])));
                        }

                        // Look for muraqib.icon or glance.icon in service labels
                        if (isset($compose['services']) && is_array($compose['services'])) {
                            // First, try to find main service
                            foreach ($compose['services'] as $serviceName => $serviceData) {
                                if (isset($serviceData['labels'])) {
                                    $labels = $this->normalizeLabels($serviceData['labels']);

                                    if ($stackData->id == 10) {
                                        \Illuminate\Support\Facades\Log::info("Debugging Stack 10: Service $serviceName labels: ".json_encode($labels));
                                    }

                                    // Check if this is main service
                                    if (isset($labels['muraqib.main']) && $labels['muraqib.main'] === 'true') {
                                        $icon = $labels['muraqib.icon'] ?? $labels['glance.icon'] ?? null;
                                        break;
                                    }
                                }
                            }

                            // If no main service with icon, find any service with icon
                            if (! $icon) {
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
                                \Illuminate\Support\Facades\Log::info('Debugging Stack 10: Final Icon Extracted: '.var_export($icon, true));
                            }
                        }
                    } catch (\Exception $e) {
                        if ($stackData->id == 10) {
                            \Illuminate\Support\Facades\Log::error('Debugging Stack 10: YAML Parse Error: '.$e->getMessage());
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
                if (! $icon) {
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
                    'external_id' => (string) $stackData->id,
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
