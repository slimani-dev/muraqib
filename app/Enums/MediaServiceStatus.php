<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Result of a server-side accessibility check.
 */
enum MediaServiceStatus: string implements HasColor, HasLabel
{
    case Up = 'up';

    /** Reachable, but the status headers were rejected (401/403). */
    case Blocked = 'blocked';

    case Down = 'down';

    public function getLabel(): string
    {
        return match ($this) {
            self::Up => 'Up',
            self::Blocked => 'Blocked',
            self::Down => 'Down',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Up => 'success',
            self::Blocked => 'warning',
            self::Down => 'danger',
        };
    }
}
