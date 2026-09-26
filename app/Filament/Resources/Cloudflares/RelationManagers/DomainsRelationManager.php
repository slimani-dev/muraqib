<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use App\Models\CloudflareDomain;
use App\Services\Cloudflare\CloudflareService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
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
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Domain Name'),

                TextInput::make('zone_id')
                    ->label('Zone ID')
                    ->maxLength(255),

                TextInput::make('status')
                    ->default('active'),

                Repeater::make('dnsRecords')
                    ->relationship()
                    ->schema([
                        Select::make('type')
                            ->options([
                                'A' => 'A',
                                'CNAME' => 'CNAME',
                                'AAAA' => 'AAAA',
                                'TXT' => 'TXT',
                                'MX' => 'MX',
                            ])
                            ->default('CNAME'),
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->placeholder('subdomain'),
                        TextInput::make('content')
                            ->label('Content')
                            ->required()
                            ->placeholder('1.2.3.4 or target'),
                        Toggle::make('proxied')
                            ->default(true),
                    ])
                    ->columns(4)
                    ->columnSpanFull()
                    ->label('DNS Records'),

                Repeater::make('accessTokens')
                    ->relationship('accessTokens')
                    ->schema([
                        TextInput::make('name')
                            ->label('Subdomain')
                            ->required()
                            ->readOnly(),
                        TextInput::make('client_id')
                            ->label('Client ID')
                            ->readOnly(),
                        TextInput::make('app_id')
                            ->label('Cloudflare App ID')
                            ->readOnly(),
                        TextInput::make('policy_id')
                            ->label('Policy ID')
                            ->readOnly(),
                    ])
                    ->columns(2)
                    ->columnSpanFull()
                    ->addable(false)
                    ->deletable(true)
                    ->label('Access Tokens (One-Click Protection)'),
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
                Action::make('sync_zones')
                    ->label('Pull Zones')
                    ->icon('mdi-cloud-sync')
                    ->action(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);
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

                            Notification::make()
                                ->title("Synced {$count} Zones")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                // CreateAction::make(),
            ])
            ->recordActions([
                Action::make('open_url')
                    ->label('Open URL')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn (CloudflareDomain $record) => "https://{$record->name}")
                    ->openUrlInNewTab(),
                Action::make('sync_dns_records')
                    ->label('Pull Records')
                    ->icon('mdi-dns')
                    ->action(function ($record) {
                        try {
                            $service = app(CloudflareService::class);
                            $records = $service->listDnsRecords($record); // $record is CloudflareDomain

                            // Clear existing records? Or Update?
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

                            Notification::make()
                                ->title("Synced {$count} DNS Records")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                // EditAction::make(),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // DeleteBulkAction::make(),
                ]),
            ]);
    }
}
