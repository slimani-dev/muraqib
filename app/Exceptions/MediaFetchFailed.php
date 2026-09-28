<?php

namespace App\Exceptions;

use App\Models\MediaService;
use RuntimeException;
use Throwable;

/**
 * A media app didn't return usable data. Thrown inside cache callbacks so the failure isn't cached.
 */
class MediaFetchFailed extends RuntimeException
{
    public static function for(MediaService $service, ?Throwable $previous = null): self
    {
        if ($previous instanceof self) {
            return $previous;
        }

        return new self("Could not fetch data from {$service->name} ({$service->type->value}).", previous: $previous);
    }
}
