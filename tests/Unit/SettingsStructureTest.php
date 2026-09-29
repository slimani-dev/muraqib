<?php

use App\Settings\DeveloperSettings;
use App\Settings\GeneralSettings;
use App\Settings\InfrastructureSettings;
use Tests\TestCase;

uses(TestCase::class);

test('general settings has correct group', function () {
    $settings = new GeneralSettings;
    expect($settings->group())->toBe('general');
});

test('infrastructure settings has correct group', function () {
    $settings = new InfrastructureSettings;
    expect($settings->group())->toBe('infrastructure');
});

test('developer settings has correct group', function () {
    $settings = new DeveloperSettings;
    expect($settings->group())->toBe('developer');
});
