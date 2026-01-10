<?php

namespace App\Filament\Resources\Portainers\Schemas;

use Filament\Schemas\Schema;

class PortainerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                \Filament\Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('url')
                    ->required()
                    ->url()
                    ->maxLength(255),
                \Filament\Forms\Components\TextInput::make('access_token')
                    ->required()
                    ->password()
                    ->revealable()
                    ->maxLength(255),
                \Filament\Forms\Components\ToggleButtons::make('status')
                    ->options(\App\Enums\PortainerStatus::class)
                    ->inline()
                    ->default(\App\Enums\PortainerStatus::Active),
            ]);
    }
}
