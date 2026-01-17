<?php

namespace App\Filament\Resources\Portainers\Schemas;

use App\Enums\PortainerStatus;
use Filament\Forms\Components\Select;
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
                    ->required(),
                TextInput::make('url')
                    ->url()
                    ->required(),
                TextInput::make('access_token')
                    ->password()
                    ->required(),
            ]);
    }
}
