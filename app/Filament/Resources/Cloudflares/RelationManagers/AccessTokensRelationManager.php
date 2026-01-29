<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use Filament\Actions\BulkActionGroup;
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

                                // 2. Import ALL tokens - no filtering
                                $targetName = $token['name'];

                                // Try to match to a domain if name starts with "Muraqib-"
                                if (\Illuminate\Support\Str::startsWith($token['name'], 'Muraqib-')) {
                                    $targetName = \Illuminate\Support\Str::after($token['name'], 'Muraqib-');
                                }

                                // Find matching domain (longest match first)
                                $matchedDomain = $domains
                                    ->filter(fn ($d) => \Illuminate\Support\Str::endsWith($targetName, $d->name))
                                    ->sortByDesc(fn ($d) => strlen($d->name))
                                    ->first();

                                // Create record even if no domain match - use first domain as fallback
                                $domainId = $matchedDomain?->id ?? $domains->first()?->id;

                                if ($domainId) {
                                    \App\Models\CloudflareAccess::create([
                                        'cloudflare_domain_id' => $domainId,
                                        'app_id' => null,
                                        'name' => $targetName,
                                        'client_id' => null,  // We don't get client_id from service token list
                                        'service_token_id' => $token['id'],  // This is the service token ID
                                        'client_secret' => null,
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
                \Filament\Actions\Action::make('reprovision')
                    ->label('Re-Provision')
                    ->icon('mdi-refresh')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Re-Provision Access Protection')
                    ->modalDescription(fn ($record) => "This will recreate the Cloudflare Access App and Service Token for {$record->name}. The old credentials will be replaced.")
                    ->action(function ($record) {
                        try {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $domain = $record->domain;

                            if (! $domain) {
                                throw new \Exception('Domain not found for this Access Token.');
                            }

                            // Delete old protection if it exists
                            try {
                                $service->deleteSubdomainProtection($domain, $record);
                            } catch (\Exception $e) {
                                // Continue even if deletion fails
                            }

                            // Re-create protection
                            $newAccess = $service->protectSubdomain($domain, $record->name);

                            // Update the existing record with new credentials
                            $record->update([
                                'app_id' => $newAccess->app_id,
                                'client_id' => $newAccess->client_id,
                                'client_secret' => $newAccess->client_secret,
                                'policy_id' => $newAccess->policy_id,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Protection Re-Provisioned')
                                ->body(new \Illuminate\Support\HtmlString("
                                    <strong>Subdomain:</strong> {$record->name}<br>
                                    <strong>New Client ID:</strong> {$record->client_id}<br>
                                    <strong>New Client Secret:</strong> {$record->client_secret}<br>
                                    <br>
                                    <span class='text-xs text-gray-500'>New credentials have been saved to the database.</span>
                                "))
                                ->persistent()
                                ->actions([
                                    \Webbingbrasil\FilamentCopyActions\Actions\CopyAction::make('copy_secret')
                                        ->label('Copy Secret')
                                        ->copyable($record->client_secret)
                                        ->icon('heroicon-m-clipboard')
                                        ->color('gray'),
                                ])
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            \Filament\Notifications\Notification::make()
                                ->title('Re-Provision Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                \Webbingbrasil\FilamentCopyActions\Actions\CopyAction::make('copy_secret')
                    ->label('Copy Secret')
                    ->icon('heroicon-m-clipboard')
                    ->color('gray')
                    ->copyable(fn ($record) => $record->client_secret),
                \Filament\Actions\Action::make('smartDelete')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->modalHeading('Delete Service Token')
                    ->modalSubmitActionLabel('Delete Token & Dependencies')
                    ->modalIcon('heroicon-o-exclamation-triangle')
                    ->form(function ($record) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        $usage = ['groups' => [], 'policies' => []];
                        $hasUsage = false;

                        // We need to fetch usage. Since this is a form, it runs when modal opens.
                        // Ideally we should cache this or it might be slow on each open.
                        try {
                            if ($record->domain && $record->domain->cloudflare) {
                                $usage = $service->findTokenUsage($record->domain->cloudflare, $record->service_token_id);
                                $hasUsage = count($usage['groups']) > 0 || count($usage['policies']) > 0;
                            }
                        } catch (\Exception $e) {
                            // If API fails, we can't be sure.
                        }

                        $blockingHtml = '';
                        if ($hasUsage) {
                            $blockingHtml .= '<ul class="list-disc pl-4 space-y-1 text-sm">';
                            foreach ($usage['policies'] as $p) {
                                $blockingHtml .= "<li><strong>Policy:</strong> {$p['name']} <span class='text-gray-500'>(App: {$p['_app_name']})</span></li>";
                            }
                            foreach ($usage['groups'] as $g) {
                                $blockingHtml .= "<li><strong>Group:</strong> {$g['name']}</li>";
                            }
                            $blockingHtml .= '</ul>';
                        }

                        return [
                            \Filament\Forms\Components\Hidden::make('has_blocking')
                                ->default($hasUsage),

                            \Filament\Forms\Components\Placeholder::make('blocking_alert')
                                ->hiddenLabel()
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="fi-fo-placeholder text-sm text-danger-600 bg-danger-50 dark:bg-danger-950/50 dark:text-danger-400 p-4 rounded-lg border border-danger-200 dark:border-danger-800 flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-medium">Blocking Dependencies Found</h3>
                                            <p class="mt-1 text-xs opacity-90">This Service Token is currently in use. Deleting it will also delete the following references:</p>
                                        </div>
                                    </div>
                                '))
                                ->visible($hasUsage),

                            \Filament\Forms\Components\Placeholder::make('blocking_list')
                                ->label('Dependencies to be removed:')
                                ->content(new \Illuminate\Support\HtmlString($blockingHtml))
                                ->visible($hasUsage),

                            \Filament\Forms\Components\Placeholder::make('safe_alert')
                                ->hiddenLabel()
                                ->content(new \Illuminate\Support\HtmlString('
                                    <div class="fi-fo-placeholder text-sm text-success-600 bg-success-50 dark:bg-success-950/50 dark:text-success-400 p-4 rounded-lg border border-success-200 dark:border-success-800 flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                        <div>
                                            <h3 class="font-medium">Safe to Delete</h3>
                                            <p class="mt-1 text-xs opacity-90">No active policies or groups are using this token.</p>
                                        </div>
                                    </div>
                                '))
                                ->visible(! $hasUsage),

                            \Filament\Forms\Components\Placeholder::make('confirmation')
                                ->label('Confirmation')
                                ->content(new \Illuminate\Support\HtmlString("Are you sure you want to delete <strong>{$record->name}</strong>? This action cannot be undone."))
                                ->visible(! $hasUsage),

                            \Filament\Forms\Components\Checkbox::make('confirm_cascade')
                                ->label('I understand that the above policies/groups will be deleted.')
                                ->required()
                                ->visible($hasUsage),
                        ];
                    })
                    ->action(function ($record, $data) {
                        $service = app(\App\Services\Cloudflare\CloudflareService::class);
                        $domain = $record->domain;

                        if (! $domain || ! $domain->cloudflare) {
                            \Filament\Notifications\Notification::make()
                                ->title('Error')
                                ->body('Cloudflare Account/Domain link missing.')
                                ->danger()
                                ->send();

                            return;
                        }

                        // 1. Delete Dependencies (if any)
                        if ($data['has_blocking'] ?? false) {
                            try {
                                $result = $service->deleteTokenDependencies($domain->cloudflare, $record->service_token_id);

                                if (count($result['errors']) > 0) {
                                    throw new \Exception('Failed to delete some dependencies: '.implode(', ', $result['errors']));
                                }

                                \Filament\Notifications\Notification::make()
                                    ->title('Dependencies Deleted')
                                    ->body('Cleaned up: '.implode(', ', $result['deleted']))
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Dependency Cleanup Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();

                                return; // Stop here
                            }
                        }

                        // 2. Delete the Subdomain Protection (App + Token + Policy linked to this specific record)
                        // Note: deleteSubdomainProtection handles the local DB deletion too? No, it returns status.
                        // And wait, deleteTokenDependencies might have ALREADY deleted the Policy if it was found via findTokenUsage.
                        // But deleteSubdomainProtection looks for specific app_id/policy_id stored in the DB record.
                        // We should proceed with deleteSubdomainProtection to ensure the main App and Token are gone.

                        $result = $service->deleteSubdomainProtection($domain, $record);

                        if ($result['success']) {
                            \Filament\Notifications\Notification::make()
                                ->title('Service Token Deleted')
                                ->body('Successfully deleted Service Token and Access App.')
                                ->success()
                                ->send();

                            $record->delete();
                        } else {
                            $message = "Database record kept due to API errors.\n".implode("\n", $result['errors']);
                            \Filament\Notifications\Notification::make()
                                ->title('Deletion Failed')
                                ->body($message)
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete Multiple Access Protections')
                        ->modalDescription('This will permanently delete the selected Access Tokens AND remove all Cloudflare protection. The subdomains will become publicly accessible.')
                        ->modalIcon('heroicon-o-shield-exclamation')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $service = app(\App\Services\Cloudflare\CloudflareService::class);
                            $fullyDeleted = 0;
                            $partiallyDeleted = 0;
                            $failedRecords = [];
                            $allErrors = [];

                            foreach ($records as $record) {
                                if ($record->domain) {
                                    $result = $service->deleteSubdomainProtection($record->domain, $record);

                                    if ($result['success']) {
                                        $fullyDeleted++;
                                        // Only delete from database if API deletion succeeded
                                        $record->delete();
                                    } else {
                                        $partiallyDeleted++;
                                        $failedRecords[] = $record->name;
                                        $allErrors[] = "{$record->name}: ".implode('; ', $result['errors']);
                                        // DO NOT delete from database - keep for retry
                                    }
                                } else {
                                    $partiallyDeleted++;
                                    $failedRecords[] = $record->name;
                                    $allErrors[] = "{$record->name}: Domain not found";
                                    // DO NOT delete from database
                                }
                            }

                            $message = "✅ Fully deleted from Cloudflare + Database: {$fullyDeleted}\n";
                            if ($partiallyDeleted > 0) {
                                $message .= "⚠️ Failed (kept in database): {$partiallyDeleted}\n\n";
                                $message .= "❌ ERRORS:\n".implode("\n", $allErrors);
                                $message .= "\n\n⚠️ Failed records kept in database for retry.";
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Bulk Deletion Complete')
                                ->body($message)
                                ->color($partiallyDeleted > 0 ? 'warning' : 'success')
                                ->persistent($partiallyDeleted > 0)
                                ->send();
                        }),
                ]),
            ]);
    }
}
