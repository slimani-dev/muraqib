<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CloudflareInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->schema([
                Section::make('General Information')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('account_id')
                            ->label('Account ID'),
                        TextEntry::make('status')
                            ->badge(),
                    ])->columns(2),
            ]);
    }
}
