<?php

namespace App\Filament\Resources\MediaServices;

use App\Filament\Resources\MediaServices\Pages\ListMediaServices;
use App\Filament\Resources\MediaServices\Schemas\MediaServiceForm;
use App\Filament\Resources\MediaServices\Tables\MediaServicesTable;
use App\Models\MediaService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class MediaServiceResource extends Resource
{
    protected static ?string $model = MediaService::class;

    protected static string|BackedEnum|null $navigationIcon = 'mdi-movie-open-settings';

    protected static ?string $navigationLabel = 'Media Services';

    public static function form(Schema $schema): Schema
    {
        return MediaServiceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaServicesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediaServices::route('/'),
        ];
    }
}
