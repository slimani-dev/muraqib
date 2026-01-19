<?php

namespace App\Filament\Resources\Netdatas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Webbingbrasil\FilamentCopyActions\Actions\CopyAction;

class NetdataInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Service Overview')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Service Name')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        TextEntry::make('status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'pending' => 'gray',
                                'active' => 'success',
                                'failed' => 'danger',
                                default => 'info',
                            }),
                        TextEntry::make('ingressRule.hostname')
                            ->label('Public URL')
                            ->formatStateUsing(function ($state, $record) {
                                $path = $record->ingressRule?->path ?? '';

                                return $state.$path;
                            })
                            ->url(function ($record) {
                                $hostname = $record->ingressRule?->hostname;
                                $path = $record->ingressRule?->path ?? '';

                                return "https://{$hostname}{$path}";
                            }, true)
                            ->icon('heroicon-m-globe-alt')
                            ->color('primary')
                            ->columnSpanFull(),
                    ]),

                Grid::make(2)
                    ->schema([
                        Section::make('Connectivity & Ingress')
                            ->icon('heroicon-m-server-stack')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('ingressRule.tunnel.name')
                                    ->label('via Tunnel')
                                    ->icon('heroicon-m-link'),
                                TextEntry::make('ingressRule.service')
                                    ->label('Internal Service')
                                    ->fontFamily('mono'),
                                TextEntry::make('ingressRule.path')
                                    ->label('Path')
                                    ->placeholder('/'),
                            ]),

                        Section::make('Zero Trust Security')
                            ->icon('heroicon-m-shield-check')
                            ->columnSpan(1)
                            ->schema([
                                TextEntry::make('access.name')
                                    ->label('Access Policy')
                                    ->placeholder('No Policy Applied'),
                                TextEntry::make('access.client_id')
                                    ->label('Client ID')
                                    ->copyable()
                                    ->fontFamily('mono')
                                    ->hintAction(
                                        CopyAction::make('copy_client_id')
                                            ->label('Copy')
                                            ->copyable(fn ($record) => $record->access->client_id)
                                    )
                                    ->visible(fn ($record) => $record->access),
                                TextEntry::make('access.client_secret')
                                    ->label('Client Secret')
                                    ->fontFamily('mono')
                                    ->formatStateUsing(fn () => '••••••••')
                                    ->hintAction(
                                        CopyAction::make('copy_client_secret')
                                            ->label('Copy')
                                            ->copyable(fn ($record) => $record->access->client_secret)
                                    )
                                    ->visible(fn ($record) => $record->access),
                            ]),
                    ]),

                Section::make('Metadata')
                    ->collapsed()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('created_at')->dateTime(),
                            TextEntry::make('updated_at')->dateTime(),
                        ]),
                    ]),
            ]);
    }
}
