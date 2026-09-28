<?php

namespace App\Filament\Resources\MediaServices\Schemas;

use App\Enums\MediaServiceType;
use App\Models\MediaService;
use App\Rules\PublicUrl;
use App\Services\Media\MediaStatusChecker;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MediaServiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $usesBasicAuth = fn (Get $get): bool => self::type($get)?->usesBasicAuth() ?? false;

        return $schema
            ->columns(2)
            ->components([
                Select::make('type')
                    ->options(MediaServiceType::class)
                    ->required()
                    ->live()
                    ->disabledOn('edit'),

                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('url')
                    ->label('Service URL')
                    ->url()
                    ->required()
                    ->maxLength(255)
                    ->helperText('Used by the server for API calls. A LAN address is fine.'),

                self::secretInput('api_key', 'API key')
                    ->visible(fn (Get $get): bool => ! $usesBasicAuth($get)),

                TextInput::make('username')
                    ->required()
                    ->visible($usesBasicAuth),

                self::secretInput('password', 'Password')
                    ->visible($usesBasicAuth),

                TextInput::make('settings.rpc_path')
                    ->label('RPC path')
                    ->default('/transmission/rpc')
                    ->required()
                    ->visible($usesBasicAuth),

                Toggle::make('is_enabled')
                    ->label('Enabled')
                    ->default(true),

                Section::make('Status check')
                    ->description('Shows whether the service is accessible. Runs in the browser, or on the server when headers are set or the URL is plain http.')
                    ->columnSpanFull()
                    ->collapsible()
                    ->schema([
                        TextInput::make('status_url')
                            ->label('Status URL')
                            ->url()
                            ->rule(new PublicUrl)
                            ->maxLength(255)
                            ->placeholder('Defaults to the service URL when that is public')
                            ->helperText('Must be reachable from the internet: the dashboard may be opened from outside your network.'),

                        KeyValue::make('status_headers')
                            ->label('Headers')
                            ->keyLabel('Header')
                            ->valueLabel('Value')
                            ->helperText('Only what the check needs to get through, e.g. a token your gateway requires. Stored encrypted; with headers set the check runs on the server.'),
                    ]),

                Actions::make([
                    Action::make('testConnection')
                        ->label('Test connection')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->action(fn (Get $get, ?MediaService $record) => self::testConnection($get, $record)),
                ])->columnSpanFull(),
            ]);
    }

    /**
     * A password field that is never filled with the stored secret. Left blank on edit, it keeps it.
     */
    private static function secretInput(string $name, string $label): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->password()
            ->revealable()
            ->formatStateUsing(fn (): ?string => null)
            ->dehydrated(fn (?string $state): bool => filled($state))
            ->required(fn (string $operation): bool => $operation === 'create')
            ->placeholder(fn (string $operation): ?string => $operation === 'edit' ? 'Stored. Leave blank to keep it' : null);
    }

    private static function testConnection(Get $get, ?MediaService $record): void
    {
        $type = self::type($get);

        if (! $type || blank($get('url'))) {
            Notification::make()->title('Pick a type and enter the service URL first.')->warning()->send();

            return;
        }

        $service = new MediaService([
            'type' => $type,
            'name' => $get('name') ?: $type->getLabel(),
            'url' => $get('url'),
            'api_key' => $get('api_key') ?: $record?->api_key,
            'username' => $get('username'),
            'password' => $get('password') ?: $record?->password,
            'settings' => $get('settings'),
            'status_url' => $get('status_url'),
            'status_headers' => $get('status_headers'),
        ]);

        $apiWorks = $service->client()->checkConnection();
        $status = $service->statusCheckUrl() !== null
            ? app(MediaStatusChecker::class)->check($service)->getLabel()
            : 'not checked (no public URL)';

        Notification::make()
            ->title($apiWorks ? 'API connected' : 'API failed')
            ->body(($apiWorks ? 'Credentials work.' : 'Check the service URL and credentials.')." Status: {$status}.")
            ->{$apiWorks ? 'success' : 'danger'}()
            ->send();
    }

    private static function type(Get $get): ?MediaServiceType
    {
        $type = $get('type');

        return $type instanceof MediaServiceType ? $type : MediaServiceType::tryFrom((string) $type);
    }
}
