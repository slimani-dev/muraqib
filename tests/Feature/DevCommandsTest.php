<?php

use Illuminate\Foundation\DevCommands;

test('artisan dev skips the server, listens on every queue and runs the scheduler', function () {
    $names = array_column(DevCommands::commands(), 'name');

    expect($names)->not->toContain('server')
        ->and($names)->toContain('queue', 'schedule', 'vite');

    $queue = collect(DevCommands::commands())->firstWhere('name', 'queue');

    expect($queue['command'])->toContain('queue:listen --queue=high,default,low');
});
