<?php

namespace Tests\Unit\Services;

use App\Services\Cloudflare\CloudflareService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareServiceTest extends TestCase
{
    public function test_verify_token_uses_account_endpoint_when_account_id_provided()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/accounts/12345/tokens/verify' => Http::response(['result' => ['status' => 'active'], 'success' => true]),
        ]);

        $service = new CloudflareService();
        $result = $service->verifyToken('token', '12345');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cloudflare.com/client/v4/accounts/12345/tokens/verify';
        });
    }

    public function test_verify_token_uses_user_endpoint_when_no_account_id()
    {
        Http::fake([
            'https://api.cloudflare.com/client/v4/user/tokens/verify' => Http::response(['result' => ['status' => 'active'], 'success' => true]),
        ]);

        $service = new CloudflareService();
        $result = $service->verifyToken('token');

        $this->assertTrue($result);
        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.cloudflare.com/client/v4/user/tokens/verify';
        });
    }
}
