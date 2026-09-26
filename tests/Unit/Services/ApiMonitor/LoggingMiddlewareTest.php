<?php

namespace Tests\Unit\Services\ApiMonitor;

use App\Models\ApiRequest;
use App\Services\ApiMonitor\LoggingMiddleware;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(RefreshDatabase::class, TestCase::class);

test('it logs successful requests', function () {
    $middleware = new LoggingMiddleware('TestService', 'testAction');
    $handler = function ($request, $options) {
        return Create::promiseFor(new Response(200, [], '{"success":true}'));
    };

    $request = new Request('GET', 'https://api.example.com/test', ['X-Api-Key' => 'secret'], '{"data":"test"}');

    $promise = $middleware($handler)($request, []);
    $promise->wait();

    $this->assertDatabaseHas('api_requests', [
        'service' => 'TestService',
        'name' => 'testAction',
        'method' => 'GET',
        'url' => 'https://api.example.com/test',
        'status_code' => 200,
        'response_body' => '{"success":true}',
    ]);

    // Assert redaction
    $log = ApiRequest::first();
    expect(json_encode($log->request_headers))->toContain('***REDACTED***');
});

test('it logs failed requests', function () {
    $middleware = new LoggingMiddleware('TestService', 'failAction');
    $handler = function ($request, $options) {
        return Create::rejectionFor(new \Exception('Connection refused'));
    };

    $request = new Request('POST', 'https://api.example.com/fail');

    try {
        $promise = $middleware($handler)($request, []);
        $promise->wait();
    } catch (\Exception $e) {
        // Needed to catch the rejection re-throw
    }

    $this->assertDatabaseHas('api_requests', [
        'service' => 'TestService',
        'name' => 'failAction',
        'error' => 'Connection refused',
        'status_code' => null,
    ]);
});
