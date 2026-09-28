<?php

namespace App\Filament\Resources\Repositories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RepositoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('git_account_id')
                    ->label('Git account')
                    ->relationship('account', 'name')
                    ->required()
                    ->columnSpanFull(),

                TextInput::make('owner')
                    ->label('Owner / workspace')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('laravel'),

                TextInput::make('name')
                    ->label('Repository')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('framework'),

                Toggle::make('is_managed')
                    ->label('Managed by me')
                    ->helperText('Managed repos appear in the Managed repositories widget.')
                    ->columnSpanFull(),

                Section::make('Display')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('display_name')
                            ->maxLength(255)
                            ->placeholder('Defaults to the repository name'),

                        TextInput::make('logo_url')
                            ->label('Logo URL')
                            ->url()
                            ->maxLength(255)
                            ->placeholder('Defaults to the Git host\'s icon'),
                    ]),

                Section::make('Package downloads')
                    ->columnSpanFull()
                    ->columns(2)
                    ->collapsible()
                    ->schema([
                        TextInput::make('packagist_package')
                            ->label('Packagist package')
                            ->maxLength(255)
                            ->placeholder('vendor/package'),

                        TextInput::make('npm_package')
                            ->label('npm package')
                            ->maxLength(255)
                            ->placeholder('package or @scope/package'),
                    ]),
            ]);
    }
}
