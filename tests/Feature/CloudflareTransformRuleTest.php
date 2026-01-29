<?php

namespace Tests\Feature;

use App\Actions\Cloudflare\SyncTransformRules;
use App\Models\Cloudflare;
use App\Models\CloudflareAccess;
use App\Models\CloudflareDomain;
use App\Models\CloudflareTransformRule;
use App\Models\Netdata;
use App\Services\Cloudflare\CloudflareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CloudflareTransformRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_deploys_rule_with_linked_services()
    {
        // 1. Setup Data
        $account = Cloudflare::create([
            'name' => 'Test Account',
            'api_token' => 'fake-token',
            'account_id' => 'acc-123',
            'status' => \App\Enums\CloudflareStatus::Active,
        ]);

        $domain = CloudflareDomain::create([
            'cloudflare_id' => $account->id,
            'zone_id' => 'zone-123',
            'name' => 'example.com',
            'status' => \App\Enums\CloudflareStatus::Active,
        ]);

        // Create Cloudflare Access for Netdata
        $access = CloudflareAccess::create([
            'cloudflare_domain_id' => $domain->id,
            'name' => 'netdata.example.com',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
        ]);

        // Create Netdata instance
        $netdata = Netdata::create([
            'name' => 'Test Netdata',
            'cloudflare_access_id' => $access->id,
            'status' => 'active',
        ]);

        // Create Portainer instance
        $portainer = \App\Models\Portainer::create([
            'name' => 'Test Portainer',
            'url' => 'https://portainer.example.com',
            'access_token' => 'portainer-token-123',
            'status' => \App\Enums\PortainerStatus::Active,
        ]);

        // Create Transform Rule
        $rule = CloudflareTransformRule::create([
            'name' => 'Service Auth',
            'cloudflare_id' => $account->id,
        ]);

        // Link services to the rule
        $rule->netdatas()->attach($netdata);
        $rule->portainers()->attach($portainer);

        // 2. Mock Cloudflare API
        Http::fake([
            'https://api.cloudflare.com/client/v4/zones/zone-123/rulesets/ruleset-abc/rules' => Http::response([
                'result' => [
                    'id' => 'new-rule-id',
                ],
                'success' => true,
            ]),

            'https://api.cloudflare.com/client/v4/zones/zone-123/rulesets*' => Http::response([
                'result' => [
                    [
                        'id' => 'ruleset-abc',
                        'phase' => 'http_request_late_transform',
                    ],
                ],
                'success' => true,
            ]),
        ]);

        // 3. Run Action
        $service = new CloudflareService;
        $action = new SyncTransformRules($service);
        $action->handle($rule);

        // 4. Assertions
        $this->assertDatabaseHas('cloudflare_transform_rules', [
            'id' => $rule->id,
        ]);

        $rule->refresh();
        $this->assertEquals(['new-rule-id'], $rule->rule_ids);

        // Assert pattern was auto-generated
        $this->assertStringContainsString('netdata\\.example\\.com', $rule->pattern);
        $this->assertStringContainsString('portainer\\.example\\.com', $rule->pattern);

        // Assert headers were auto-generated
        $this->assertArrayHasKey('CF-Access-Client-Id', $rule->headers);
        $this->assertEquals('test-client-id', $rule->headers['CF-Access-Client-Id']);
        $this->assertArrayHasKey('CF-Access-Client-Secret', $rule->headers);
        $this->assertEquals('test-client-secret', $rule->headers['CF-Access-Client-Secret']);
        $this->assertArrayHasKey('Authorization', $rule->headers);
        $this->assertEquals('Bearer portainer-token-123', $rule->headers['Authorization']);

        // Assert API Call with correct pattern and headers
        Http::assertSent(function ($request) {
            if ($request->url() !== 'https://api.cloudflare.com/client/v4/zones/zone-123/rulesets/ruleset-abc/rules') {
                return false;
            }

            $data = $request->data();

            // Check Expression contains both hostnames
            $hasNetdata = str_contains($data['expression'], 'netdata\\.example\\.com');
            $hasPortainer = str_contains($data['expression'], 'portainer\\.example\\.com');

            // Check Headers - CF headers without operation, regular headers with operation
            $headers = $data['action_parameters']['headers'];

            // CF-Access headers should NOT have operation field
            $this->assertArrayHasKey('CF-Access-Client-Id', $headers);
            $this->assertArrayNotHasKey('operation', $headers['CF-Access-Client-Id']);
            $this->assertEquals('test-client-id', $headers['CF-Access-Client-Id']['value']);

            $this->assertArrayHasKey('CF-Access-Client-Secret', $headers);
            $this->assertArrayNotHasKey('operation', $headers['CF-Access-Client-Secret']);
            $this->assertEquals('test-client-secret', $headers['CF-Access-Client-Secret']['value']);

            // Authorization header SHOULD have operation field
            $this->assertArrayHasKey('Authorization', $headers);
            $this->assertEquals('set', $headers['Authorization']['operation']);
            $this->assertEquals('Bearer portainer-token-123', $headers['Authorization']['value']);

            return $hasNetdata && $hasPortainer;
        });
    }
}
