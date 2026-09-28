<?php

namespace App\Filament\Resources\Repositories\Pages;

use App\Filament\Resources\Repositories\RepositoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRepositories extends ListRecords
{
    protected static string $resource = RepositoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
