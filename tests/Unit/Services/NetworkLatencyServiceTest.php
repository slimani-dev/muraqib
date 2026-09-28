<?php

use App\Services\NetworkLatencyService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

uses(TestCase::class);

const PING_OUTPUT = <<<'TXT'
PING google.com (142.250.199.206) 56(84) bytes of data.
3 packets transmitted, 3 received, 0% packet loss, time 2003ms
rtt min/avg/max/mdev = 20.101/24.512/30.004/4.1 ms
TXT;

it('pings google.com and reads the average round trip', function () {
    Process::fake(['*' => Process::result(PING_OUTPUT)]);

    expect(app(NetworkLatencyService::class)->latency())
        ->ms->toBe(24.5)
        ->host->toBe('google.com');
});

it('pings at most once per cache window unless a fresh measurement is asked for', function () {
    Process::fake(['*' => Process::result(PING_OUTPUT)]);
    $latency = app(NetworkLatencyService::class);

    $latency->latency();
    $latency->latency();
    Process::assertRanTimes(fn ($process) => $process->command[0] === 'ping', 1);

    $latency->latency(fresh: true);
    Process::assertRanTimes(fn ($process) => $process->command[0] === 'ping', 2);

    expect(Cache::has(NetworkLatencyService::CACHE_KEY))->toBeTrue();
});
