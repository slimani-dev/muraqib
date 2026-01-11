<?php

namespace App\Filament\Resources\Cloudflares\Schemas;

use App\Models\Cloudflare;
use Filament\Forms;
use Filament\Schemas\Schema;

class DnsRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Forms\Components\Select::make('cloudflare_domain_id')
                    ->label('Zone')
                    ->options(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $livewire->getOwnerRecord();

                        return $account->domains->pluck('name', 'id');
                    })
                    ->default(function ($livewire) {
                        /** @var Cloudflare $account */
                        $account = $livewire->getOwnerRecord();

                        // Auto-select first zone if only one exists
                        return $account->domains->count() === 1 ? $account->domains->first()->id : null;
                    })
                    ->searchable()
                    ->required(),

                Forms\Components\Select::make('type')
                    ->label('Record Type')
                    ->options([
                        'A' => 'A - IPv4 Address',
                        'AAAA' => 'AAAA - IPv6 Address',
                        'CNAME' => 'CNAME - Canonical Name',
                        'TXT' => 'TXT - Text Record',
                        'MX' => 'MX - Mail Exchange',
                        'NS' => 'NS - Name Server',
                        'SRV' => 'SRV - Service',
                    ])
                    ->default('CNAME')
                    ->required()
                    ->helperText('Select the DNS record type'),

                Forms\Components\TextInput::make('name')
                    ->label('Name')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('subdomain or @ for root')
                    ->helperText('Enter the subdomain or @ for root domain'),

                Forms\Components\TextInput::make('content')
                    ->label('Content')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('IPv4, IPv6, or target domain')
                    ->helperText('The target value for this DNS record'),

                Forms\Components\TextInput::make('ttl')
                    ->label('TTL (Time To Live)')
                    ->numeric()
                    ->default(1) // Auto
                    ->minValue(1)
                    ->maxValue(2147483647)
                    ->helperText('1 for Auto, or specify seconds (e.g., 3600 for 1 hour)'),

                Forms\Components\Toggle::make('proxied')
                    ->label('Proxy through Cloudflare')
                    ->default(true)
                    ->helperText('Enable to proxy traffic through Cloudflare (orange cloud)'),
            ]);
    }
}
