<?php

namespace App\Services\ApiMonitor;

use App\Models\ApiRequest;
use Illuminate\Support\Facades\Auth;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class LoggingMiddleware
{
    public function __construct(
        protected string $serviceName,
        protected ?string $actionName = null
    ) {}

    public function __invoke(callable $handler): callable
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $startTime = microtime(true);
            $userId = Auth::id();
            $ip = request()->ip();

            return $handler($request, $options)->then(
                function (ResponseInterface $response) use ($request, $startTime, $userId, $ip) {
                    $this->log($request, $response, $startTime, null, $userId, $ip);

                    return $response;
                },
                function ($reason) use ($request, $startTime, $userId, $ip) {
                    $error = $reason instanceof \Throwable ? $reason->getMessage() : (string) $reason;
                    $this->log($request, null, $startTime, $error, $userId, $ip);

                    return \GuzzleHttp\Promise\Create::rejectionFor($reason);
                }
            );
        };
    }

    protected function log(RequestInterface $request, ?ResponseInterface $response, float $startTime, ?string $error, ?int $userId, ?string $ip): void
    {
        try {
            $duration = round((microtime(true) - $startTime) * 1000); // ms

            $requestBody = $this->getBody($request);
            $responseBody = $response ? $this->getBody($response) : null;

            ApiRequest::create([
                'service' => $this->serviceName,
                'name' => $this->actionName,
                'method' => $request->getMethod(),
                'url' => (string) $request->getUri(),
                'request_headers' => $this->sanitizeHeaders($request->getHeaders()),
                'request_body' => $this->sanitizeBody($requestBody),
                'status_code' => $response?->getStatusCode(),
                'response_headers' => $response ? $this->sanitizeHeaders($response->getHeaders()) : null,
                'response_body' => $this->sanitizeBody($responseBody),
                'duration_ms' => $duration,
                'error' => $error,
                'user_id' => $userId,
                'ip_address' => $ip,
            ]);
        } catch (\Throwable $e) {
            // Failsafe: Do not break the app if logging fails
            \Illuminate\Support\Facades\Log::error('Failed to log API Request: '.$e->getMessage());
        }
    }

    protected function getBody(MessageInterface $message): ?string
    {
        try {
            $message->getBody()->rewind();
            $content = $message->getBody()->getContents();
            $message->getBody()->rewind();

            return $content;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sensitive = ['authorization', 'x-auth-key', 'x-api-key', 'x-auth-email', 'set-cookie', 'cookie'];

        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $headers[$key] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    protected function sanitizeBody(?string $body): ?string
    {
        if (empty($body)) {
            return null;
        }

        // Simple JSON scrubbing
        if ($json = json_decode($body, true)) {
            $this->scrubJson($json);

            return json_encode($json);
        }

        return $body;
    }

    protected function scrubJson(array &$data): void
    {
        $sensitiveKeys = ['token', 'password', 'secret', 'client_secret', 'key', 'access_token'];

        foreach ($data as $key => &$value) {
            if (is_array($value)) {
                $this->scrubJson($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $value = '***REDACTED***';
            }
        }
    }
}
