<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\Cloudflare;
use App\Models\CloudflareServiceToken;
use App\Services\Cloudflare\CloudflareService;
use Carbon\Carbon; // v4 Unified
use Filament\Actions\Action; // v4 Unified
use Filament\Actions\CreateAction; // v4 Unified
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Webbingbrasil\FilamentCopyActions\Actions\CopyAction;

class AccessTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'serviceTokens';

    protected static ?string $title = 'Service Tokens';

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
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('token_id')
                    ->label('Token ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('sync_tokens')
                    ->label('Pull Tokens')
                    ->icon('mdi-cloud-sync')
                    ->action(function () {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);
                        try {
                            if (! $account->api_token) {
                                throw new \Exception('API Token missing.');
                            }

                            // 1. Fetch all Service Tokens from Account
                            $tokens = $service->listServiceTokens($account);

                            $count = 0;
                            $updated = 0;
                            $created = 0;

                            foreach ($tokens as $token) {
                                $existing = CloudflareServiceToken::where('token_id', $token['id'])->first();

                                if ($existing) {
                                    $existing->update([
                                        'name' => $token['name'],
                                        'expires_at' => isset($token['expires_at']) ? Carbon::parse($token['expires_at']) : null,
                                        'updated_at' => now(),
                                    ]);
                                    $updated++;
                                } else {
                                    $account->serviceTokens()->create([
                                        'token_id' => $token['id'],
                                        'name' => $token['name'],
                                        'client_id' => $token['client_id'] ?? null, // API might not return this in list
                                        'expires_at' => isset($token['expires_at']) ? Carbon::parse($token['expires_at']) : null,
                                    ]);
                                    $created++;
                                }
                            }

                            Notification::make()
                                ->title('Synced Tokens')
                                ->body("Updated: {$updated}, Imported: {$created}")
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

                CreateAction::make()
                    ->label('Create Token')
                    ->icon('heroicon-o-plus')
                    ->schema([ // v4: Use schema() instead of form()
                        TextInput::make('name')
                            ->label('Token Name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('A descriptive name for this service token.'),
                        Select::make('duration')
                            ->label('Duration')
                            ->options([
                                '8760h' => '1 Year',
                                'non_expiring' => 'Non-expiring',
                            ])
                            ->default('8760h')
                            ->required(),
                    ])
                    ->action(function (array $data, CreateAction $action) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);

                        try {
                            // Call API
                            $result = $service->createServiceToken($account, $data['name'], $data['duration']);

                            // Create DB Record
                            $record = $account->serviceTokens()->create([
                                'token_id' => $result['id'],
                                'name' => $data['name'],
                                'client_id' => $result['client_id'],
                                'client_secret' => $result['client_secret'],
                                'expires_at' => isset($result['expires_at']) ? Carbon::parse($result['expires_at']) : null,
                            ]);

                            // Notify with Secret
                            Notification::make()
                                ->title('Service Token Created')
                                ->body(new HtmlString("
                                    <strong>Client ID:</strong> {$record->client_id}<br>
                                    <strong>Client Secret:</strong> {$record->client_secret}<br>
                                    <br>
                                    <span class='text-xs text-gray-500'>Please copy the secret now. It will generally not be visible again via API.</span>
                                "))
                                ->persistent()
                                ->actions([
                                    CopyAction::make('copy_secret')
                                        ->label('Copy Secret')
                                        ->copyable($record->client_secret)
                                        ->icon('heroicon-m-clipboard')
                                        ->color('gray'),
                                ])
                                ->success()
                                ->send();

                            return $record;

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Creation Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();

                            $action->halt();
                        }
                    }),
            ])
            ->actions([
                CopyAction::make('copy_client_id')
                    ->label('Client ID')
                    ->copyable(fn ($record) => $record->client_id)
                    ->icon('heroicon-m-clipboard'),

                CopyAction::make('copy_secret')
                    ->label('Client Secret')
                    ->copyable(fn ($record) => $record->client_secret)
                    ->icon('heroicon-m-lock-closed')
                    ->visible(fn ($record) => filled($record->client_secret)),

                DeleteAction::make()
                    ->label('Delete')
                    ->action(function ($record) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);

                        try {
                            $service->deleteServiceToken($account, $record->token_id);

                            $record->delete();

                            Notification::make()
                                ->title('Deleted')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Delete Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->action(function ($records) {
                        /** @var Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(CloudflareService::class);

                        foreach ($records as $record) {
                            try {
                                $service->deleteServiceToken($account, $record->token_id);
                                $record->delete();
                            } catch (\Exception $e) {
                                // Log error but continue? Or notify?
                            }
                        }
                    }),
            ]);
    }
}
