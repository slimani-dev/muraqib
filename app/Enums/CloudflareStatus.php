<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum CloudflareStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Healthy = 'healthy';
    case Degraded = 'degraded';
    case Down = 'down';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Inactive => 'Inactive',
            self::Healthy => 'Healthy',
            self::Degraded => 'Degraded',
            self::Down => 'Down',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active, self::Healthy => 'success',
            self::Inactive => 'gray',
            self::Degraded => 'warning',
            self::Down => 'danger',
        };
    }
}
