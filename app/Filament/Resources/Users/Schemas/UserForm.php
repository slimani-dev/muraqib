<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                TextInput::make('password')
                    ->password()
                    ->revealable()
                    ->rule(Password::defaults())
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->placeholder(fn (string $operation): ?string => $operation === 'edit' ? 'Leave blank to keep the current password' : null),

                Toggle::make('is_admin')
                    ->label('Admin')
                    ->helperText('Admins can open this admin panel and manage integrations, credentials and users.')
                    // Nobody can take away their own admin access (and lock themselves out)
                    ->disabled(fn (?User $record): bool => $record?->is(auth()->user()) ?? false)
                    ->inline(false),
            ]);
    }
}
