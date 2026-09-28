<?php

namespace App\Filament\Resources\GitAccounts\Pages;

use App\Filament\Resources\GitAccounts\GitAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGitAccounts extends ListRecords
{
    protected static string $resource = GitAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
