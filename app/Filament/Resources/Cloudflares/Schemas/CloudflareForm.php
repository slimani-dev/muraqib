<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
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
                    ->placeholder('e.g. Production Cloudflare'),
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

                ToggleButtons::make('status')
                    ->options(\App\Enums\CloudflareStatus::class)
                    ->inline()
                    ->default(\App\Enums\CloudflareStatus::Active)
                    ->hidden(),
            ]);
    }
}
