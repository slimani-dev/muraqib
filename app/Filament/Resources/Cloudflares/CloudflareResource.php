<?php

namespace App\Filament\Resources\Cloudflares;

use App\Filament\Resources\Cloudflares\Pages\ListCloudflares;
use App\Filament\Resources\Cloudflares\Pages\ViewCloudflare;
use App\Filament\Resources\Cloudflares\Schemas\CloudflareForm;
use App\Filament\Resources\Cloudflares\Schemas\CloudflareInfolist;
use App\Filament\Resources\Cloudflares\Tables\CloudflaresTable;
use App\Models\Cloudflare;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class CloudflareResource extends Resource
{
    protected static ?string $model = Cloudflare::class;

    protected static string|BackedEnum|null $navigationIcon = 'si-cloudflare';

    public static function form(Schema $schema): Schema
    {
        return CloudflareForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CloudflareInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CloudflaresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TunnelsRelationManager::class,
            RelationManagers\DomainsRelationManager::class,
            RelationManagers\IngressRulesRelationManager::class,
            RelationManagers\DnsRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCloudflares::route('/'),
            'view' => ViewCloudflare::route('/{record}'),
        ];
    }
}
