<?php

namespace App\Contracts;

interface MonitorableService
{
    /**
     * Get the name of the service for logging purposes.
     */
    public function getServiceName(): string;
}
