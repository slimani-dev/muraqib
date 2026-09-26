<?php

namespace App\Filament\Resources\Cloudflares\Resources;

use App\Filament\Resources\Cloudflares\RelationManagers\AccessPoliciesRelationManager;
use App\Filament\Resources\Cloudflares\Resources\Pages\ListCloudflareAccessApplications;
use App\Filament\Resources\Cloudflares\Resources\Pages\ViewCloudflareAccessApplication;
use App\Models\CloudflareAccessApplication;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CloudflareAccessApplicationResource extends Resource
{
    protected static ?string $model = CloudflareAccessApplication::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'cloudflare-access-applications';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                // Read only details
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('domain'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            AccessPoliciesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCloudflareAccessApplications::route('/'),
            'view' => ViewCloudflareAccessApplication::route('/{record}'),
        ];
    }
}
