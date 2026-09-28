<?php

namespace App\Services;

use Illuminate\Process\Exceptions\ProcessTimedOutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

/**
 * Internet latency for the network widget when Netdata has no ping chart.
 * Pings google.com from the server at most twice an hour (cached 30 minutes);
 * the widget's refresh button can force a new measurement.
 */
class NetworkLatencyService
{
    public const HOST = 'google.com';

    public const CACHE_KEY = 'network_latency';

    public const CACHE_SECONDS = 1800;

    /**
     * @return array{ms: float, host: string, measured_at: string}|null
     */
    public function latency(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        $cached = Cache::get(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $ms = $this->measure();

        if ($ms === null) {
            return null;
        }

        $result = ['ms' => $ms, 'host' => self::HOST, 'measured_at' => now()->toIso8601String()];
        Cache::put(self::CACHE_KEY, $result, self::CACHE_SECONDS);

        return $result;
    }

    /**
     * Average round-trip time in milliseconds: ICMP ping when the `ping` binary works,
     * otherwise the time to open a TCP connection to port 443.
     */
    public function measure(): ?float
    {
        try {
            $result = Process::timeout(10)->run(['ping', '-c', '3', '-W', '2', self::HOST]);

            if ($result->successful() && preg_match('#= [\d.]+/([\d.]+)/#', $result->output(), $matches)) {
                return round((float) $matches[1], 1);
            }
        } catch (ProcessTimedOutException) {
            // Fall back to a TCP connection below.
        }

        return $this->tcpConnectTime();
    }

    private function tcpConnectTime(): ?float
    {
        $start = hrtime(true);
        $socket = @fsockopen('ssl://'.self::HOST, 443, $errorCode, $errorMessage, 3);

        if ($socket === false) {
            return null;
        }

        fclose($socket);

        return round((hrtime(true) - $start) / 1e6, 1);
    }
}
