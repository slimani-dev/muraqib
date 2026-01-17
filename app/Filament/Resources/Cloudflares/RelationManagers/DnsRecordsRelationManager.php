<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
        return \App\Filament\Resources\Cloudflares\Schemas\DnsRecordForm::configure($schema);
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
                    ->color(fn (string $state): string => match ($state) {
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
                                return $state.' ('.$tunnel->name.')';
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
                    ->formatStateUsing(fn ($state) => $state == 1 ? 'Auto' : $state),
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
                    ->icon('mdi-cloud-download')
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
                            /** @var CloudflareDomain $domain */
                            $domain = CloudflareDomain::findOrFail($data['domain_id']);
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

                CreateAction::make()
                    ->label('New DNS Record')
                    ->using(function (array $data, string $model) {
                        $domain = CloudflareDomain::findOrFail($data['cloudflare_domain_id']);
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);

                        // Create Remote
                        $remote = $service->createRemoteDnsRecord($domain, [
                            'type' => $data['type'],
                            'name' => $data['name'],
                            'content' => $data['content'],
                            'ttl' => (int) $data['ttl'],
                            'proxied' => (bool) ($data['proxied'] ?? false),
                        ]);

                        // Create Local
                        $data['record_id'] = $remote['id'];

                        return \App\Models\CloudflareDnsRecord::create($data);
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->using(function (\Illuminate\Database\Eloquent\Model $record, array $data) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        $domain = $record->domain;

                        // Check if record_id exists
                        if ($record->record_id) {
                            // Update Remote
                            $service->updateRemoteDnsRecord($domain, $record->record_id, [
                                'type' => $data['type'],
                                'name' => $data['name'],
                                'content' => $data['content'],
                                'ttl' => (int) $data['ttl'],
                                'proxied' => (bool) ($data['proxied'] ?? false),
                            ]);
                        } else {
                            // Create Remote (Push logic for previously unsynced record)
                            $remote = $service->createRemoteDnsRecord($domain, [
                                'type' => $data['type'],
                                'name' => $data['name'],
                                'content' => $data['content'],
                                'ttl' => (int) $data['ttl'],
                                'proxied' => (bool) ($data['proxied'] ?? false),
                            ]);
                            $data['record_id'] = $remote['id'];

                            \Filament\Notifications\Notification::make()
                                ->title('Record Synced')
                                ->body('Local record was unsynced; created on Cloudflare.')
                                ->success()
                                ->send();
                        }

                        $record->update($data);

                        return $record;
                    }),
                ActionGroup::make([
                    \Filament\Actions\Action::make('push_dns_record')
                        ->label('Push to Cloudflare')
                        ->icon('mdi-cloud-upload')
                        ->color('success')
                        ->visible(fn ($record) => empty($record->record_id))
                        ->action(function ($record) {
                            try {
                                $service = app(\App\Services\Cloudflare\CloudflareService::class);

                                $remote = $service->createRemoteDnsRecord($record->domain, [
                                    'type' => $record->type,
                                    'name' => $record->name,
                                    'content' => $record->content,
                                    'ttl' => (int) $record->ttl,
                                    'proxied' => (bool) $record->proxied,
                                ]);

                                $record->update(['record_id' => $remote['id']]);

                                \Filament\Notifications\Notification::make()
                                    ->title('Record Pushed')
                                    ->body("{$record->name} synced to Cloudflare.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Push Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteAction::make()
                        ->before(function (\App\Models\CloudflareDnsRecord $record) {
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
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
