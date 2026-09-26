<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use App\Enums\CloudflareStatus;
use App\Services\Cloudflare\CloudflareService;
use CodeWithDennis\SimpleAlert\Components\SimpleAlert;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;

class CloudflareForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                TextInput::make('name')
                    ->maxLength(255)
                    ->placeholder('e.g. Production Cloudflare')
                    ->default('My Cloudflare'),
                TextInput::make('account_id')
                    ->label('Account ID')
                    ->required()
                    ->maxLength(255),
                TextInput::make('api_token')
                    ->label('API Token')
                    ->password()
                    ->revealable()
                    ->required()
                    ->maxLength(255),

                SimpleAlert::make('connection_success')
                    ->success()
                    ->title('Connection Valid')
                    ->description('Successfully connected to Cloudflare API.')
                    ->visible(fn ($get) => $get('connection_status') === 'success'),

                SimpleAlert::make('connection_error')
                    ->danger()
                    ->title('Connection Failed')
                    ->description(fn ($get) => $get('connection_message'))
                    ->visible(fn ($get) => $get('connection_status') === 'error'),

                Actions::make([
                    Action::make('checkConnection')
                        ->label('Test Connection')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->action(function ($get, $set) {
                            $token = $get('api_token');

                            if (! $token) {
                                $set('connection_status', 'error');
                                $set('connection_message', 'API Token is required.');

                                return;
                            }

                            $service = app(CloudflareService::class);
                            $accountId = $get('account_id');

                            try {
                                if ($service->verifyToken($token, $accountId)) {
                                    $set('connection_status', 'success');
                                    $set('connection_message', 'Connected successfully.');
                                } else {
                                    $set('connection_status', 'error');
                                    $set('connection_message', 'Invalid API Token. Verification failed.');
                                }
                            } catch (\Exception $e) {
                                $set('connection_status', 'error');
                                $set('connection_message', 'Error connecting to Cloudflare: '.$e->getMessage());
                            }
                        }),
                ]),

                Hidden::make('connection_status')
                    ->default('pending')
                    ->live()
                    ->dehydrated(),
                Hidden::make('connection_message'),

                ToggleButtons::make('status')
                    ->options(CloudflareStatus::class)
                    ->inline()
                    ->default(CloudflareStatus::Active)
                    ->hidden(),
            ]);
    }
}
