<?php

namespace App\Filament\Resources\ApiRequests\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ApiRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('service')
                    ->required(),
                TextInput::make('name'),
                TextInput::make('method')
                    ->required(),
                Textarea::make('url')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('request_headers'),
                Textarea::make('request_body')
                    ->columnSpanFull(),
                TextInput::make('status_code')
                    ->numeric(),
                TextInput::make('response_headers'),
                Textarea::make('response_body')
                    ->columnSpanFull(),
                TextInput::make('duration_ms')
                    ->numeric(),
                Textarea::make('error')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->relationship('user', 'name'),
                TextInput::make('ip_address'),
                TextInput::make('meta'),
            ]);
    }
}
