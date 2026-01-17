<?php

namespace App\Filament\Resources\Portainers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PortainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->default('My Portainer'),
                TextInput::make('url')
                    ->url()
                    ->required(),
                TextInput::make('access_token')
                    ->password()
                    ->required(),

                \CodeWithDennis\SimpleAlert\Components\SimpleAlert::make('connection_success')
                    ->success()
                    ->title('Connection Valid')
                    ->description('Successfully connected to Portainer API.')
                    ->visible(fn ($get) => $get('connection_status') === 'success'),

                \CodeWithDennis\SimpleAlert\Components\SimpleAlert::make('connection_error')
                    ->danger()
                    ->title('Connection Failed')
                    ->description(fn ($get) => $get('connection_message'))
                    ->visible(fn ($get) => $get('connection_status') === 'error'),

                \Filament\Schemas\Components\Actions::make([
                    \Filament\Actions\Action::make('checkConnection')
                        ->label('Test Connection')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->action(function ($get, $set) {
                            $url = $get('url');
                            $token = $get('access_token');

                            if (! $url || ! $token) {
                                $set('connection_status', 'error');
                                $set('connection_message', 'URL and Access Token are required.');

                                return;
                            }

                            // Temporary Portainer instance for checking
                            $tempPortainer = new \App\Models\Portainer([
                                'url' => $url,
                                'access_token' => $token,
                            ]);

                            $service = new \App\Services\PortainerService($tempPortainer);

                            if ($service->checkConnection()) {
                                $set('connection_status', 'success');
                                $set('connection_message', 'Connected successfully.');
                            } else {
                                $set('connection_status', 'error');
                                $set('connection_message', 'Could not connect to Portainer. Check URL and Token.');
                            }
                        }),
                ]),

                \Filament\Forms\Components\Hidden::make('connection_status')
                    ->default('pending')
                    ->live() // Important for reactivity
                    ->dehydrated(),
                \Filament\Forms\Components\Hidden::make('connection_message'),
            ]);
    }
}
