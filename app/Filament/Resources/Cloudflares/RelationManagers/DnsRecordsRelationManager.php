<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DnsRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'dnsRecords';

    protected static ?string $title = 'DNS Records';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('cloudflare_domain_id')
                    ->label('Zone')
                    ->options(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        return $account->domains->pluck('name', 'id');
                    })
                    ->searchable()
                    ->required(),
                Select::make('type')
                    ->options([
                        'A' => 'A',
                        'AAAA' => 'AAAA',
                        'CNAME' => 'CNAME',
                        'TXT' => 'TXT',
                        'MX' => 'MX',
                        'NS' => 'NS',
                        'SRV' => 'SRV',
                    ])
                    ->default('CNAME')
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('subdomain or @'),
                TextInput::make('content')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('IPv4 or target'),
                TextInput::make('ttl')
                    ->label('TTL')
                    ->numeric()
                    ->default(1) // Auto
                    ->helperText('1 for Auto'),
                Toggle::make('proxied')
                    ->default(true),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'A', 'AAAA' => 'success',
                        'CNAME' => 'info',
                        'TXT' => 'gray',
                        'MX' => 'warning',
                        default => 'primary',
                    })
                    ->sortable(),
                TextColumn::make('content')
                    ->limit(50)
                    ->toggleable()
                    ->formatStateUsing(function ($state) {
                        // Check if content looks like a tunnel UUID
                        if (preg_match('/^([a-f0-9-]{36})\.cfargotunnel\.com$/', $state, $matches)) {
                            $uuid = $matches[1];
                            $tunnel = \App\Models\CloudflareTunnel::where('tunnel_id', $uuid)->first();
                            if ($tunnel) {
                                return $state . ' (' . $tunnel->name . ')';
                            }
                        }

                        return $state;
                    })
                    ->description(function ($state) {
                        if (preg_match('/^([a-f0-9-]{36})\.cfargotunnel\.com$/', $state, $matches)) {
                            $uuid = $matches[1];
                            $tunnel = \App\Models\CloudflareTunnel::where('tunnel_id', $uuid)->first();

                            return $tunnel ? "Tunnel: {$tunnel->name}" : null;
                        }

                        return null;
                    }),
                \Filament\Tables\Columns\IconColumn::make('proxied')
                    ->boolean(),
                TextColumn::make('ttl')
                    ->label('TTL')
                    ->formatStateUsing(fn($state) => $state == 1 ? 'Auto' : $state),
                TextColumn::make('domain.name')
                    ->label('Zone')
                    ->searchable()
                    ->sortable(),
            ])
            ->deferFilters(false)
            ->filtersLayout(\Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'A' => 'A',
                        'AAAA' => 'AAAA',
                        'CNAME' => 'CNAME',
                        'TXT' => 'TXT',
                        'MX' => 'MX',
                    ]),
                \Filament\Tables\Filters\SelectFilter::make('cloudflare_domain_id')
                    ->label('Zone')
                    ->relationship('domain', 'name')
                    ->searchable()
                    ->preload(),
                \Filament\Tables\Filters\SelectFilter::make('tunnel_id')
                    ->label('Tunnel')
                    ->options(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();

                        // Get tunnels for this account
                        return \App\Models\CloudflareTunnel::where('cloudflare_id', $account->id)
                            ->pluck('name', 'tunnel_id');
                    })
                    ->query(function ($query, array $data) {
                        if (empty($data['value'])) {
                            return;
                        }
                        $tunnelId = $data['value'];
                        $query->where('content', 'LIKE', "{$tunnelId}.cfargotunnel.com");
                    })
                    ->searchable(),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->deferFilters(false)
            ->headerActions([
                \Filament\Actions\Action::make('sync_dns_records')
                    ->label('Pull Records')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->slideOver(false)
                    ->schema([
                        \Filament\Forms\Components\Select::make('domain_id')
                            ->label('Select Zone')
                            ->options(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->domains->pluck('name', 'id');
                            })
                            ->default(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->domains->count() === 1 ? $account->domains->first()->id : null;
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                        try {
                            $domain = \App\Models\CloudflareDomain::findOrFail($data['domain_id']);
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);

                            $records = $service->listDnsRecords($domain);

                            $domain->dnsRecords()->delete();

                            $count = 0;
                            foreach ($records as $item) {
                                $domain->dnsRecords()->create([
                                    'record_id' => $item['id'] ?? null,
                                    'type' => $item['type'],
                                    'name' => $item['name'],
                                    'content' => $item['content'],
                                    'proxied' => $item['proxied'] ?? false,
                                    'ttl' => $item['ttl'] ?? 0,
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Synced {$count} Records for {$domain->name}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                \Filament\Actions\Action::make('sync_from_ingress')
                    ->slideOver(false)
                    ->label('Sync from Ingress')
                    ->icon('heroicon-o-globe-alt')
                    ->color('warning')
                    ->schema([
                        \Filament\Forms\Components\Select::make('tunnel_id')
                            ->label('Select Tunnel')
                            ->options(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->tunnels->pluck('name', 'id');
                            })
                            ->default(function ($livewire) {
                                /** @var Cloudflare $account */
                                $account = $this->getOwnerRecord();

                                return $account->tunnels->count() === 1 ? $account->tunnels->first()->id : null;
                            })
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (array $data, $livewire) {
                         try {
                            /** @var \App\Models\CloudflareTunnel $tunnel */
                            $tunnel = \App\Models\CloudflareTunnel::with('ingressRules')->findOrFail($data['tunnel_id']);
                            
                            /** @var Cloudflare $account */
                            $account = $this->getOwnerRecord();
                            $domains = $account->domains;
                            
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $results = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'error' => 0];

                            foreach ($tunnel->ingressRules as $rule) {
                                if (empty($rule->hostname) || $rule->hostname === '*') {
                                    continue;
                                }

                                // Find matching zone
                                $matchedDomain = $domains->first(function($domain) use ($rule) {
                                    return str_ends_with($rule->hostname, $domain->name);
                                });

                                if (! $matchedDomain) {
                                    continue;
                                }

                                try {
                                    $status = $service->ensureCnameRecord(
                                        $matchedDomain, 
                                        $rule->hostname, 
                                        "{$tunnel->tunnel_id}.cfargotunnel.com"
                                    );
                                    
                                    if (isset($results[$status])) {
                                        $results[$status]++;
                                    }
                                } catch (\Exception $e) {
                                    $results['error']++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('DNS Records from Ingress Synced')
                                ->body("Created: {$results['created']}, Updated: {$results['updated']}, Skipped: {$results['skipped']}, Errors: {$results['error']}")
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                CreateAction::make()
                    ->label('New DNS Record')
                    ->using(function (array $data, string $model) {
                        $domain = \App\Models\CloudflareDomain::findOrFail($data['cloudflare_domain_id']);
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        // Create Remote
                        $remote = $service->createRemoteDnsRecord($domain, [
                            'type' => $data['type'],
                            'name' => $data['name'],
                            'content' => $data['content'],
                            'ttl' => (int)$data['ttl'],
                            'proxied' => (bool)($data['proxied'] ?? false),
                        ]);

                        // Create Local
                        $data['record_id'] = $remote['id'];

                        return \App\Models\CloudflareDnsRecord::create($data);
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->using(function (\Illuminate\Database\Eloquent\Model $record, array $data) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        $domain = $record->domain;

                        // Update Remote
                        if ($record->record_id) {
                            $service->updateRemoteDnsRecord($domain, $record->record_id, [
                                'type' => $data['type'],
                                'name' => $data['name'],
                                'content' => $data['content'],
                                'ttl' => (int)$data['ttl'],
                                'proxied' => (bool)($data['proxied'] ?? false),
                                // 'comment' => ...
                            ]);
                        }

                        $record->update($data);

                        return $record;
                    }),
                DeleteAction::make()
                    ->before(function (\Illuminate\Database\Eloquent\Model $record) {
                        try {
                            if ($record->record_id) {
                                $service = app(\App\Services\Cloudflare\CloudflareService::class);
                                $service->deleteRemoteDnsRecord($record->domain, $record->record_id);
                            }
                        } catch (\Exception $e) {
                            // Log or notify, but proceed with local delete?
                            // Or fail? Best to fail and notify.
                            \Filament\Notifications\Notification::make()
                                ->title('Remote Delete Failed')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                            // throw $e; // Throwing prevents local delete? Yes.
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
