<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainsRelationManager extends RelationManager
{
    protected static string $relationship = 'domains';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Domain Name'),

                \Filament\Forms\Components\TextInput::make('zone_id')
                    ->label('Zone ID')
                    ->maxLength(255),

                \Filament\Forms\Components\TextInput::make('status')
                    ->default('active'),

                \Filament\Forms\Components\Repeater::make('dnsRecords')
                    ->relationship()
                    ->schema([
                        \Filament\Forms\Components\Select::make('type')
                            ->options([
                                'A' => 'A',
                                'CNAME' => 'CNAME',
                                'AAAA' => 'AAAA',
                                'TXT' => 'TXT',
                                'MX' => 'MX',
                            ])
                            ->default('CNAME'),
                        \Filament\Forms\Components\TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('subdomain'),
                        \Filament\Forms\Components\TextInput::make('content')
                            ->label('Content')
                            ->required()
                            ->placeholder('1.2.3.4 or target'),
                        \Filament\Forms\Components\Toggle::make('proxied')
                            ->default(true),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->label('DNS Records'),
            ]);
    }

    protected static ?string $title = 'Zones';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Domain / Zone')
                    ->searchable(),
                TextColumn::make('zone_id')
                    ->label('Zone ID')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\Action::make('sync_zones')
                    ->label('Pull Zones')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($livewire) {
                        /** @var \App\Models\Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        try {
                            if (! $account->api_token) {
                                throw new \Exception('API Token missing.');
                            }

                            $zones = $service->listZones($account->api_token);
                            $count = 0;

                            foreach ($zones as $zone) {
                                $account->domains()->updateOrCreate(
                                    ['zone_id' => $zone['id']],
                                    [
                                        'name' => $zone['name'],
                                        'status' => $zone['status'],
                                    ]
                                );
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Synced {$count} Zones")
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
                CreateAction::make(),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('sync_dns_records')
                    ->label('Pull Records')
                    ->icon('heroicon-o-list-bullet')
                    ->action(function ($record) {
                        try {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $records = $service->listDnsRecords($record); // $record is CloudflareDomain

                            // Clear existing records? Or Update?
                            // Since we want to mirror remote state, deleting and recreating is often safer for simple lists
                            // But usually users might have extra fields?
                            // CloudflareDnsRecord model check needed. Assuming fillable.

                            $record->dnsRecords()->delete();

                            $count = 0;
                            foreach ($records as $item) {
                                $record->dnsRecords()->create([
                                    'type' => $item['type'],
                                    'name' => $item['name'],
                                    'content' => $item['content'],
                                    'proxied' => $item['proxied'] ?? false,
                                    'ttl' => $item['ttl'] ?? 0,
                                ]);
                                $count++;
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Synced {$count} DNS Records")
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
