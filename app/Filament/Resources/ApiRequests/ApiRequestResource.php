<?php

namespace App\Filament\Resources\ApiRequests;

use App\Filament\Resources\ApiRequests\Pages\CreateApiRequest;
use App\Filament\Resources\ApiRequests\Pages\EditApiRequest;
use App\Filament\Resources\ApiRequests\Pages\ListApiRequests;
use App\Filament\Resources\ApiRequests\Pages\ViewApiRequest;
use App\Filament\Resources\ApiRequests\Schemas\ApiRequestForm;
use App\Filament\Resources\ApiRequests\Schemas\ApiRequestInfolist;
use App\Filament\Resources\ApiRequests\Tables\ApiRequestsTable;
use App\Models\ApiRequest;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ApiRequestResource extends Resource
{
    protected static ?string $model = ApiRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function infolist(Schema $schema): Schema
    {
        return ApiRequestInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiRequestsTable::configure($table);
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
            'index' => ListApiRequests::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
