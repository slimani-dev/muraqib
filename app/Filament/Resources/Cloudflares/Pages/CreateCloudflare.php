<?php

namespace App\Filament\Resources\Cloudflares\Pages;

use App\Filament\Resources\Cloudflares\CloudflareResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCloudflare extends CreateRecord
{
    protected static string $resource = CloudflareResource::class;
}
