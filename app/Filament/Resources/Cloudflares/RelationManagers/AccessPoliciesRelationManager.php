<?php

namespace App\Filament\Resources\Cloudflares\RelationManagers;

use App\Models\CloudflareAccessApplication;
use App\Models\CloudflareAccessPolicy;
use App\Models\CloudflareServiceToken;
use App\Services\Cloudflare\CloudflareService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput; // v4
use Filament\Notifications\Notification; // v4
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccessPoliciesRelationManager extends RelationManager
{
    protected static string $relationship = 'policies';

    protected static ?string $title = 'Access Policies';

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
                    ->label('Policy Name')
                    ->searchable(),
                TextColumn::make('decision')
                    ->label('Decision')
                    ->badge(),
                TextColumn::make('serviceTokens.name')
                    ->label('Service Tokens')
                    ->badge(),
            ])
            ->headerActions([
                Action::make('sync_policies')
                    ->label('Pull Policies')
                    ->icon('mdi-cloud-sync')
                    ->action(function () {
                        /** @var CloudflareAccessApplication $app */
                        $appRec = $this->getOwnerRecord();
                        $account = $appRec->account;
                        $service = app(CloudflareService::class);

                        try {
                            $policies = $service->listAccessPolicies($account, $appRec->app_id);

                            foreach ($policies as $policy) {
                                $localPolicy = CloudflareAccessPolicy::updateOrCreate(
                                    ['policy_id' => $policy['id']],
                                    [
                                        'application_id' => $appRec->id,
                                        'name' => $policy['name'],
                                        'decision' => $policy['decision'],
                                    ]
                                );

                                // Sync attached tokens?
                                // That's harder because listAccessPolicies returns include rules.
                                // Logic: check include array for service_tokens.
                                // For now, maybe skipped or implemented if needed.
                            }
                            Notification::make()->title('Synced Policies')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('Sync Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),

                CreateAction::make()
                    ->label('Create Policy')
                    ->schema([ // v4: schema()
                        TextInput::make('name')
                            ->required(),
                        Select::make('decision')
                            ->options([
                                'non_identity' => 'Non-Identity (Service Token)',
                                'allow' => 'Allow',
                                'deny' => 'Deny',
                            ])
                            ->default('non_identity')
                            ->required(),
                        Select::make('service_tokens')
                            ->label('Attach Service Tokens')
                            ->multiple()
                            ->options(function () {
                                // Get Service Tokens from the SAME Account
                                $account = $this->getOwnerRecord()->account;

                                return $account->serviceTokens()->pluck('name', 'id');
                            })
                            ->required(),
                    ])
                    ->action(function (array $data, CreateAction $action) {
                        /** @var CloudflareAccessApplication $appRec */
                        $appRec = $this->getOwnerRecord();
                        $account = $appRec->account;
                        $service = app(CloudflareService::class);

                        try {
                            // Construct Include Array
                            // We need token_id (the Cldouflare UUID), not the local ID?
                            // CreateAccessPolicy uses token_id format: [['service_token' => ['token_id' => '...']]]

                            $serviceTokens = CloudflareServiceToken::whereIn('id', $data['service_tokens'])->get();
                            $include = [];

                            foreach ($serviceTokens as $st) {
                                $include[] = ['service_token' => ['token_id' => $st->token_id]];
                            }

                            $res = $service->createAccessPolicy($account, $appRec->app_id, $data['name'], $data['decision'], $include);

                            $policy = $appRec->policies()->create([
                                'policy_id' => $res['id'],
                                'name' => $data['name'],
                                'decision' => $data['decision'],
                            ]);

                            // Attach local pivot
                            $policy->serviceTokens()->attach($serviceTokens->pluck('id'));

                            return $policy;

                        } catch (\Exception $e) {
                            Notification::make()->title('Failed')->body($e->getMessage())->danger()->send();
                            $action->halt();
                        }
                    }),
            ])
            ->actions([
                DeleteAction::make()
                    ->action(function ($record) {
                        $appRec = $this->getOwnerRecord();
                        $account = $appRec->account;
                        $service = app(CloudflareService::class);

                        try {
                            $service->deleteAccessPolicy($account, $appRec->app_id, $record->policy_id);
                            $record->delete();
                        } catch (\Exception $e) {
                            Notification::make()->title('Delete Failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ]);
    }
}
