<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Filament\Resources\Cloudflares\Resources\CloudflareAccessApplicationResource;
use App\Models\Cloudflare;
use App\Models\CloudflareAccessApplication;
use App\Models\CloudflareDomain;
use App\Services\Cloudflare\CloudflareService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction; // v4
use Filament\Forms\Components\Select; // v4
// v4
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessApplicationsRelationManager extends RelationManager
{
    protected static string $relationship = 'accessApplications';

    protected static ?string $title = 'Access Applications';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Application Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('domain')
                    ->label('Domain')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
            ])
            ->headerActions([
                Action::make('sync_apps')
                    ->label('Pull Apps')
                    ->icon('mdi-cloud-sync')
                    ->action(function () {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);

                        try {
                            $apps = $service->listAccessApplications($account);
                            $count = 0;

                            foreach ($apps as $app) {
                                CloudflareAccessApplication::updateOrCreate(
                                    ['app_id' => $app['id']],
                                    [
                                        'account_id' => $account->id,
                                        'name' => $app['name'],
                                        'domain' => $app['domain'],
                                        'type' => $app['type'],
                                    ]
                                );
                                $count++;
                            }

                            Notification::make()->title("Synced {$count} Applications")->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Sync Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                CreateAction::make()
                    ->label('Create Application')
                    ->schema([ // v4: schema()
                        Select::make('domain_id')
                            ->label('Zone')
                            ->options(fn () => $this->getOwnerRecord()->domains()->pluck('name', 'id'))
                            ->required()
                            ->live(),
                        TextInput::make('subdomain')
                            ->label('Subdomain')
                            ->prefix('https://')
                            ->suffix(fn ($get) => '.'.(CloudflareDomain::find($get('domain_id'))?->name ?? 'example.com'))
                            ->required(),
                        TextInput::make('name')
                            ->label('Application Name')
                            ->default(fn ($get) => 'Protect '.$get('subdomain'))
                            ->required(),
                    ])
                    ->action(function (array $data, CreateAction $action) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);
                        $domain = CloudflareDomain::find($data['domain_id']);

                        $fullDomain = $data['subdomain'].'.'.$domain->name;

                        try {
                            $res = $service->createAccessApplication($account, $data['name'], $fullDomain);

                            return $account->accessApplications()->create([
                                'app_id' => $res['id'],
                                'name' => $data['name'],
                                'domain' => $res['domain'],
                                'type' => $res['type'],
                            ]);

                        } catch (\Exception $e) {
                            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                            $action->halt();
                        }
                    }),
            ])
            ->actions([
                // Link to Manage Policies
                Action::make('manage')
                    ->label('Manage Policies')
                    ->icon('heroicon-o-cog')
                    ->url(fn ($record) => CloudflareAccessApplicationResource::getUrl('view', ['record' => $record])),

                DeleteAction::make()
                    ->action(function ($record) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);

                        try {
                            $service->deleteAccessApplication($account, $record->app_id);
                            $record->delete();
                        } catch (\Exception $e) {
                            Notification::make()->title('Delete Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
