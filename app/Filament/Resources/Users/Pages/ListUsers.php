<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\Teams\CreateTeam;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->mutateDataUsing(fn (array $data): array => [...$data, 'email_verified_at' => now()])
                // Every user gets their own team (with its default dashboard page)
                ->after(fn (User $record) => app(CreateTeam::class)->handle($record, "{$record->name}'s Team", isPersonal: true)),
        ];
    }
}
