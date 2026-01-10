<?php

namespace App\Filament\Resources\Portainers;

use App\Filament\Resources\Portainers\Pages\ListPortainers;
use App\Filament\Resources\Portainers\Schemas\PortainerForm;
use App\Filament\Resources\Portainers\Tables\PortainersTable;
use App\Models\Portainer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PortainerResource extends Resource
{
    protected static ?string $model = Portainer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PortainerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PortainersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPortainers::route('/'),
        ];
    }
}
