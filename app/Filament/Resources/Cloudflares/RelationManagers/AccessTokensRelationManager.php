<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessTokensRelationManager extends RelationManager
{
    protected static string $relationship = 'accessTokens';

    protected static ?string $title = 'Access Tokens';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->label('Name / Subdomain')
                    ->searchable(),
                TextColumn::make('client_id')
                    ->label('Client ID')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('domain.name')
                    ->label('Zone')
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                \Filament\Actions\Action::make('sync_tokens')
                    ->label('Pull Tokens')
                    ->icon('mdi-cloud-sync')
                    ->action(function () {
                        /** @var \App\Models\Cloudflare $account */
                        $account = $this->getOwnerRecord();
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        try {
                            if (! $account->api_token) {
                                throw new \Exception('API Token missing.');
                            }

                            // 1. Fetch all Service Tokens from Account
                            $tokens = $service->listServiceTokens($account);

                            // Force fresh domains
                            $domains = $account->domains()->get();

                            // 2. Iterate and update/create
                            // Note: Service Tokens returned by API don't strictly possess a "domain_id"
                            // unless we map them via internal naming convention or metadata.
                            // However, we can update EXISTING ones.
                            // For NEW ones, we don't know which CloudflareDomain (Zone) to attach to
                            // without more heuristic matching (e.g. matching name to zone name).

                            $count = 0;
                            $updated = 0;
                            $created = 0;

                            // Force fresh domains
                            $domains = $account->domains()->get();

                            foreach ($tokens as $token) {
                                // 1. Check if token already linked
                                $existing = \App\Models\CloudflareAccess::where('client_id', $token['id'])->first();

                                if ($existing) {
                                    $existing->update([
                                        'name' => $token['name'],
                                        'updated_at' => now(),
                                    ]);
                                    $updated++;

                                    continue;
                                }

                                // 2. Heuristic Import: matches "StartWith Muraqib-"
                                // Example: "Muraqib-sub.domain.com"
                                // We need to find if "sub.domain.com" belongs to any of our Zones.

                                if (! \Illuminate\Support\Str::startsWith($token['name'], 'Muraqib-')) {
                                    continue;
                                }

                                $targetName = \Illuminate\Support\Str::after($token['name'], 'Muraqib-');
                                // Determine Zone: Iterative check
                                // Find a domain where targetName ends with domain->name
                                $matchedDomain = $domains
                                    ->filter(fn ($d) => \Illuminate\Support\Str::endsWith($targetName, $d->name))
                                    ->sortByDesc(fn ($d) => strlen($d->name)) // Longest match first (e.g. sub.example.com vs example.com)
                                    ->first();

                                if ($matchedDomain) {
                                    \App\Models\CloudflareAccess::create([
                                        'cloudflare_domain_id' => $matchedDomain->id,
                                        'app_id' => null, // We don't know the app ID from service token list
                                        'name' => $targetName, // "sub.domain.com"
                                        'client_id' => $token['id'],
                                        'client_secret' => null, // Secret is lost/hidden
                                        'policy_id' => null,
                                    ]);
                                    $created++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Synced Tokens')
                                ->body("Updated: {$updated}, Imported: {$created}")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),

                \Filament\Actions\Action::make('protect_subdomain')
                    ->label('Protect Subdomain')
                    ->icon('mdi-security')
                    ->color('success')
                    ->form([
                        \Filament\Forms\Components\Select::make('domain_id')
                            ->label('Domain')
                            ->options(function () {
                                return $this->getOwnerRecord()->domains()->pluck('name', 'id');
                            })
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn ($set) => $set('hostname', null)),

                        \Filament\Forms\Components\Select::make('hostname')
                            ->label('Subdomain')
                            ->helperText('Select the DNS record to protect.')
                            ->required()
                            ->searchable()
                            ->options(function ($get) {
                                $domainId = $get('domain_id');
                                if (! $domainId) {
                                    return [];
                                }

                                $domain = \App\Models\CloudflareDomain::find($domainId);

                                return $domain ? $domain->dnsRecords()
                                    ->where('type', 'CNAME')
                                    ->pluck('name', 'name') : [];
                            })
                            ->visible(fn ($get) => filled($get('domain_id'))),
                    ])
                    ->action(function (array $data) {
                        try {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);

                            $domain = \App\Models\CloudflareDomain::findOrFail($data['domain_id']);
                            $fullHostname = $data['hostname'];

                            $access = $service->protectSubdomain($domain, $fullHostname);

                            \Filament\Notifications\Notification::make()
                                ->title('Protection Enabled')
                                ->body(new \Illuminate\Support\HtmlString("
                                    <strong>Subdomain:</strong> {$access->name}<br>
                                    <strong>Client ID:</strong> {$access->client_id}<br>
                                    <strong>Client Secret:</strong> {$access->client_secret}<br>
                                    <br>
                                    <span class='text-xs text-gray-500'>Credentials have been saved to the database.</span>
                                "))
                                ->persistent()
                                ->actions([
                                    \Webbingbrasil\FilamentCopyActions\Actions\CopyAction::make('copy_secret')
                                        ->label('Copy Secret')
                                        ->copyable($access->client_secret)
                                        ->icon('heroicon-m-clipboard')
                                        ->color('gray'),
                                ])
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Protection Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                \Webbingbrasil\FilamentCopyActions\Actions\CopyAction::make('copy_secret')
                    ->label('Copy Secret')
                    ->icon('heroicon-m-clipboard')
                    ->color('gray')
                    ->copyable(fn ($record) => $record->client_secret),
                DeleteAction::make()
                    ->before(function ($record) {
                        try {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $domain = $record->domain;
                            // If domain is missing (orphan record?), we can't proceed easily.
                            if ($domain) {
                                $service->deleteSubdomainProtection($domain, $record);
                            }
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cloudflare Deletion Failed')
                                ->body($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            foreach ($records as $record) {
                                try {
                                    if ($record->domain) {
                                        $service->deleteSubdomainProtection($record->domain, $record);
                                    }
                                } catch (\Exception $e) {
                                    // Log or notify? For bulk, maybe just continue
                                }
                            }
                        }),
                ]),
            ]);
    }
}
