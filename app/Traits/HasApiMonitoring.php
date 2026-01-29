<?php

namespace App\Traits;

use App\Services\ApiMonitor\LoggingMiddleware;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait HasApiMonitoring
{
    /**
     * Get a configured Http client with monitoring enabled.
     */
    protected function http(?string $action = null): PendingRequest
    {
        $serviceName = $this->getMonitorServiceName();

        return Http::withMiddleware(new LoggingMiddleware($serviceName, $action));
    }

    protected function getMonitorServiceName(): string
    {
        if (method_exists($this, 'getServiceName')) {
            return $this->getServiceName();
        }

        if (property_exists($this, 'serviceName')) {
            return $this->serviceName;
        }

        return class_basename($this);
    }
}
