<?php

namespace App\Filament\Resources\Netdatas;

use App\Filament\Resources\Netdatas\Pages\CreateNetdata;
use App\Filament\Resources\Netdatas\Pages\EditNetdata;
use App\Filament\Resources\Netdatas\Pages\ListNetdatas;
use App\Filament\Resources\Netdatas\Pages\ViewNetdata;
use App\Filament\Resources\Netdatas\Schemas\NetdataForm;
use App\Filament\Resources\Netdatas\Schemas\NetdataInfolist;
use App\Filament\Resources\Netdatas\Tables\NetdatasTable;
use App\Models\Netdata;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class NetdataResource extends Resource
{
    protected static ?string $model = Netdata::class;

    protected static string|BackedEnum|null $navigationIcon = 'si-netdata';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return NetdataForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NetdataInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NetdatasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            // \App\Filament\Resources\Netdatas\Widgets\NetdataSystemInfo::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNetdatas::route('/'),
            // 'create' => CreateNetdata::route('/create'),
            'view' => ViewNetdata::route('/{record}'),
            // 'edit' => EditNetdata::route('/{record}/edit'),
        ];
    }
}
