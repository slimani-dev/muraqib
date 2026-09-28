<?php

namespace App\Filament\Resources\GitAccounts\Schemas;

use App\Enums\GitProvider;
use App\Models\GitAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class GitAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('provider')
                    ->options(GitProvider::class)
                    ->required()
                    ->live(),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('e.g. GitHub (personal)'),

                TextInput::make('base_url')
                    ->label('Instance URL')
                    ->url()
                    ->maxLength(255)
                    ->required(fn (Get $get): bool => self::provider($get)?->requiresBaseUrl() ?? false)
                    ->placeholder(fn (Get $get): ?string => self::provider($get)?->defaultBaseUrl() ?? 'https://git.example.com')
                    ->helperText('Only for self-hosted instances. Leave blank for github.com, gitlab.com, codeberg.org or bitbucket.org.'),

                TextInput::make('username')
                    ->helperText('Only for Bitbucket app passwords. Leave blank to use the token as an access token.')
                    ->visible(fn (Get $get): bool => self::provider($get) === GitProvider::Bitbucket),

                TextInput::make('token')
                    ->label(fn (Get $get): string => self::provider($get) === GitProvider::Bitbucket ? 'App password or access token' : 'Access token')
                    ->password()
                    ->revealable()
                    ->formatStateUsing(fn (): ?string => null)
                    ->dehydrated(fn (?string $state): bool => filled($state))
                    ->placeholder(fn (string $operation): string => $operation === 'edit' ? 'Stored. Leave blank to keep it' : 'Optional for public repos')
                    ->helperText('Read-only access is enough. Without a token, GitHub allows only 60 requests an hour.')
                    ->columnSpanFull(),

                Actions::make([
                    Action::make('testConnection')
                        ->label('Test connection')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->action(function (Get $get, ?GitAccount $record): void {
                            $provider = self::provider($get);

                            if (! $provider) {
                                Notification::make()->title('Pick a provider first.')->warning()->send();

                                return;
                            }

                            $account = new GitAccount([
                                'provider' => $provider,
                                'name' => $get('name'),
                                'base_url' => $get('base_url'),
                                'username' => $get('username'),
                                'token' => $get('token') ?: $record?->token,
                            ]);

                            $account->client()->checkConnection()
                                ? Notification::make()->title('Connected')->success()->send()
                                : Notification::make()->title('Connection failed')->body('Check the instance URL and token.')->danger()->send();
                        }),
                ])->columnSpanFull(),
            ]);
    }

    private static function provider(Get $get): ?GitProvider
    {
        $provider = $get('provider');

        return $provider instanceof GitProvider ? $provider : GitProvider::tryFrom((string) $provider);
    }
}
