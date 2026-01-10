<?php

namespace Tests\Feature;

use App\Models\CloudflareConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Assuming settings routes are protected by auth
        $this->actingAs(User::factory()->create());
    }

    public function test_verify_token_successful()
    {
        Http::fake([
            'api.cloudflare.com/client/v4/user/tokens/verify' => Http::response(['result' => ['status' => 'active']], 200),
        ]);

        $response = $this->postJson(route('settings.cloudflare.verify'), [
            'account_id' => 'test_account',
            'api_token' => 'test_token',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('cloudflare_configs', [
            'account_id' => 'test_account',
        ]);

        // Check encryption
        $config = CloudflareConfig::first();
        $this->assertEquals('test_token', $config->api_token); // Should match decrypted
        $this->assertNotEquals('test_token', $config->getRawOriginal('api_token')); // Raw should be encrypted
    }

    public function test_create_tunnel_successful()
    {
        // Setup initial config using factory logic or direct create
        $config = CloudflareConfig::create([
            'account_id' => 'acc_123',
            'api_token' => 'tok_123',
        ]);

        Http::fake([
            // List tunnels - empty
            'api.cloudflare.com/client/v4/accounts/*/tunnels?is_deleted=false' => Http::response(['result' => []], 200),
            // Create tunnel
            'api.cloudflare.com/client/v4/accounts/*/tunnels' => Http::response(['result' => ['id' => 'uuid-tunnel', 'name' => 'muraqib-node']], 200),
            // Get token
            'api.cloudflare.com/client/v4/accounts/*/cfd_tunnel/*/token' => Http::response(['result' => 'ey.LONG_ENOUGH_TOKEN_TO_PASS_VALIDATION_CHECK_WHICH_REQUIRES_50_CHARS_MINIMUM_LENGTH.token'], 200),
            'api.cloudflare.com/client/v4/zones' => Http::response(['result' => [['id' => 'zone_1', 'name' => 'example.com']]], 200),
        ]);

        $response = $this->postJson(route('settings.cloudflare.tunnel'));

        $response->assertOk()
            ->assertJson(['tunnel_id' => 'uuid-tunnel', 'tunnel_token' => 'ey.LONG_ENOUGH_TOKEN_TO_PASS_VALIDATION_CHECK_WHICH_REQUIRES_50_CHARS_MINIMUM_LENGTH.token']);

        $config->refresh();
        $this->assertEquals('uuid-tunnel', $config->tunnel_id);
        $this->assertEquals('ey.LONG_ENOUGH_TOKEN_TO_PASS_VALIDATION_CHECK_WHICH_REQUIRES_50_CHARS_MINIMUM_LENGTH.token', $config->tunnel_token);
    }

    public function test_update_ingress_and_dns()
    {
        $config = CloudflareConfig::create([
            'account_id' => 'acc_123',
            'api_token' => 'tok_123',
            'tunnel_id' => 'uuid-tunnel',
        ]);

        Http::fake([
            // Update ingress
            'api.cloudflare.com/client/v4/accounts/*/tunnels/*/configurations' => Http::response(['success' => true], 200),
            // Create DNS
            'api.cloudflare.com/client/v4/zones/*/dns_records' => Http::response(['success' => true], 200),
        ]);

        $response = $this->postJson(route('settings.cloudflare.ingress'), [
            'zone_id' => 'zone_1',
            'services' => [
                ['hostname' => 'test.example.com', 'service' => 'http://localhost:80'],
            ],
        ]);

        $response->assertOk();
        $config->refresh();
        $this->assertTrue($config->is_active);
        $this->assertEquals('zone_1', $config->domain_zone_id);
    }
}
