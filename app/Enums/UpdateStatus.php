<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum UpdateStatus: string implements HasColor, HasIcon, HasLabel
{
    case Unknown = 'unknown';
    case UpToDate = 'up_to_date';
    case UpdateAvailable = 'update_available';
    case Error = 'error';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Unknown => 'Unknown',
            self::UpToDate => 'Up to date',
            self::UpdateAvailable => 'Update available',
            self::Error => 'Error',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Unknown => 'gray',
            self::UpToDate => 'success',
            self::UpdateAvailable => 'warning',
            self::Error => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Unknown => 'heroicon-o-question-mark-circle',
            self::UpToDate => 'heroicon-o-check-circle',
            self::UpdateAvailable => 'heroicon-o-arrow-up-circle',
            self::Error => 'heroicon-o-exclamation-circle',
        };
    }
}
