<?php

namespace App\Filament\Resources\ApiRequests\Schemas;

use Filament\Forms\Components\KeyValue;
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
                    ->default('API Runner'),
                TextInput::make('name'),
                Select::make('method')
                    ->options([
                        'GET' => 'GET',
                        'POST' => 'POST',
                        'PUT' => 'PUT',
                        'PATCH' => 'PATCH',
                        'DELETE' => 'DELETE',
                    ])
                    ->required()
                    ->default('GET'),
                TextInput::make('url')
                    ->required()
                    ->url()
                    ->columnSpanFull(),
                KeyValue::make('request_headers')
                    ->columnSpanFull()
                    ->keyLabel('Header Name')
                    ->valueLabel('Value'),
                Textarea::make('request_body')
                    ->columnSpanFull(),
            ]);
    }
}
