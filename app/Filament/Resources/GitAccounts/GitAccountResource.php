<?php

namespace App\Filament\Resources\GitAccounts;

use App\Filament\Resources\GitAccounts\Pages\ListGitAccounts;
use App\Filament\Resources\GitAccounts\Schemas\GitAccountForm;
use App\Filament\Resources\GitAccounts\Tables\GitAccountsTable;
use App\Models\GitAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class GitAccountResource extends Resource
{
    protected static ?string $model = GitAccount::class;

    protected static string|BackedEnum|null $navigationIcon = 'mdi-source-branch';

    protected static ?string $navigationLabel = 'Git Accounts';

    public static function form(Schema $schema): Schema
    {
        return GitAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GitAccountsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGitAccounts::route('/'),
        ];
    }
}
