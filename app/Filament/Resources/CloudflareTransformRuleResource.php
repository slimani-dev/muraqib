<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CloudflareTransformRuleResource\Pages;
use App\Models\CloudflareTransformRule;
use Filament\Actions;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class CloudflareTransformRuleResource extends Resource
{
    protected static ?string $model = CloudflareTransformRule::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static string|\UnitEnum|null $navigationGroup = 'Cloudflare';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('cloudflare_id')
                    ->relationship('cloudflare', 'name')
                    ->required(),

                Section::make('Linked Services')
                    ->description('Select Netdata and/or Portainer instances. Pattern and headers will be auto-generated.')
                    ->schema([
                        CheckboxList::make('netdatas')
                            ->label('Netdata Instances')
                            ->relationship('netdatas', 'name')
                            ->helperText('Select Netdata instances to include in this Transform Rule'),

                        CheckboxList::make('portainers')
                            ->label('Portainer Instances')
                            ->relationship('portainers', 'name')
                            ->helperText('Select Portainer instances to include in this Transform Rule'),
                    ]),

                Section::make('Auto-Generated Configuration')
                    ->description('Pattern and headers are automatically generated based on linked services')
                    ->schema([
                        Placeholder::make('pattern_preview')
                            ->label('URL Pattern (Auto-Generated)')
                            ->content(fn ($record) => $record?->pattern ?? 'Pattern will be generated after saving'),

                        Placeholder::make('headers_preview')
                            ->label('Custom Headers (Auto-Generated)')
                            ->content(fn ($record) => $record?->headers
                                ? collect($record->headers)->map(fn ($v, $k) => "$k: $v")->implode("\n")
                                : 'Headers will be generated after saving'
                            ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cloudflare.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('pattern')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListCloudflareTransformRules::route('/'),
            'create' => Pages\CreateCloudflareTransformRule::route('/create'),
            'edit' => Pages\EditCloudflareTransformRule::route('/{record}/edit'),
        ];
    }
}
