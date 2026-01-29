<?php

namespace App\Filament\Resources\ApiRequests\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ApiRequestInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                \Filament\Schemas\Components\Section::make('Summary')
                    ->schema([
                        TextEntry::make('service')->badge(),
                        TextEntry::make('name')->badge()->placeholder('-'),
                        TextEntry::make('method')->badge(),
                        TextEntry::make('status_code')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state >= 200 && $state < 300 => 'success',
                                $state >= 400 && $state < 500 => 'warning',
                                $state >= 500 => 'danger',
                                default => 'gray'
                            }),
                        TextEntry::make('duration_ms')->suffix(' ms'),
                        TextEntry::make('created_at')->dateTime(),
                    ])->columns(6),

                \Filament\Schemas\Components\Section::make('Request Details')
                    ->schema([
                        TextEntry::make('url')->columnSpanFull()->copyable(),
                        \Filament\Infolists\Components\KeyValueEntry::make('request_headers')
                            ->columnSpanFull()
                            ->keyLabel('Header Name')
                            ->valueLabel('Header Value')
                            ->getStateUsing(fn ($record) => collect($record->request_headers ?? [])
                                ->map(fn ($value) => is_array($value) ? implode(', ', $value) : $value)
                                ->all()),
                        TextEntry::make('request_body')
                            ->columnSpanFull()
                            ->markdown()
                            ->formatStateUsing(fn ($state) => $state ? '```json'.PHP_EOL.(is_string($state) ? json_encode(json_decode($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_encode($state, JSON_PRETTY_PRINT)).PHP_EOL.'```' : '-'),
                    ]),

                \Filament\Schemas\Components\Section::make('Response Details')
                    ->schema([
                        \Filament\Infolists\Components\KeyValueEntry::make('response_headers')
                            ->columnSpanFull()
                            ->keyLabel('Header Name')
                            ->valueLabel('Header Value')
                            ->getStateUsing(fn ($record) => collect($record->response_headers ?? [])
                                ->map(fn ($value) => is_array($value) ? implode(', ', $value) : $value)
                                ->all()),
                        TextEntry::make('response_body')
                            ->columnSpanFull()
                            ->markdown()
                            ->formatStateUsing(fn ($state) => $state ? '```json'.PHP_EOL.(is_string($state) ? json_encode(json_decode($state), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : json_encode($state, JSON_PRETTY_PRINT)).PHP_EOL.'```' : '-'),
                        TextEntry::make('error')
                            ->color('danger')
                            ->columnSpanFull()
                            ->visible(fn ($state) => ! empty($state)),
                    ]),
            ]);
    }
}
