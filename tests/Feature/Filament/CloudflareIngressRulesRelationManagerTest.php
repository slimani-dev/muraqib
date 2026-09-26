<?php

use App\Filament\Resources\Cloudflares\Pages\ViewCloudflare;
use App\Filament\Resources\Cloudflares\RelationManagers\IngressRulesRelationManager;
use App\Models\Cloudflare;
use App\Models\CloudflareIngressRule;
use App\Models\CloudflareTunnel;
use Livewire\Livewire;

it('can search ingress rules by service url', function () {
    $cloudflare = Cloudflare::factory()->create();

    $tunnel = CloudflareTunnel::create([
        'cloudflare_id' => $cloudflare->id,
        'tunnel_id' => fake()->uuid(),
        'name' => 'Home tunnel',
        'status' => 'active',
    ]);

    $matchingRule = CloudflareIngressRule::create([
        'cloudflare_tunnel_id' => $tunnel->id,
        'hostname' => 'app.example.com',
        'service' => 'http://192.168.1.2:8080',
    ]);

    $otherRule = CloudflareIngressRule::create([
        'cloudflare_tunnel_id' => $tunnel->id,
        'hostname' => 'admin.example.com',
        'service' => 'http://192.168.1.3:8080',
    ]);

    Livewire::test(IngressRulesRelationManager::class, [
        'ownerRecord' => $cloudflare,
        'pageClass' => ViewCloudflare::class,
    ])
        ->searchTable('192.168.1.2')
        ->assertCanSeeTableRecords([$matchingRule])
        ->assertCanNotSeeTableRecords([$otherRule]);
});
