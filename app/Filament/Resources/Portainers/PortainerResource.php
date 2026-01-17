<?php

namespace App\Filament\Resources\Portainers;

use App\Filament\Resources\Portainers\Pages\ListPortainers;
use App\Filament\Resources\Portainers\Pages\ViewPortainer;
use App\Filament\Resources\Portainers\Schemas\PortainerForm;
use App\Filament\Resources\Portainers\Schemas\PortainerInfolist;
use App\Filament\Resources\Portainers\Tables\PortainersTable;
use App\Models\Portainer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;

use Filament\Tables\Table;

class PortainerResource extends Resource
{
    protected static ?string $model = Portainer::class;

    protected static string|BackedEnum|null $navigationIcon = 'si-portainer';

    public static function form(Schema $schema): Schema
    {
        return PortainerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PortainerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PortainersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\StacksRelationManager::class,
            RelationManagers\ContainersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortainers::route('/'),
            'view' => ViewPortainer::route('/{record}'),
        ];
    }
}
