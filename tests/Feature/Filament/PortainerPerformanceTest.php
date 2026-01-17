<?php

use App\Filament\Resources\Portainers\RelationManagers\ContainersRelationManager;
use App\Filament\Resources\Portainers\RelationManagers\StacksRelationManager;
use App\Models\Container;
use App\Models\Portainer;
use App\Models\Stack;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('analyzes StacksRelationManager performance', function () {
    $portainer = Portainer::factory()->create();
    Stack::factory()->count(20)->create(['portainer_id' => $portainer->id]);
    
    DB::enableQueryLog();
    
    Livewire::test(StacksRelationManager::class, [
        'ownerRecord' => $portainer,
        'pageClass' => \App\Filament\Resources\Portainers\Pages\ViewPortainer::class,
    ]);
    
    $queries = DB::getQueryLog();
    $queryCount = count($queries);
    
    dump("StacksRelationManager Query Count: $queryCount");
    foreach ($queries as $query) {
        // dump($query['sql'], $query['time']);
    }

    expect($queryCount)->toBeLessThan(10); 
});

it('analyzes ContainersRelationManager performance', function () {
    $portainer = Portainer::factory()->create();
    Container::factory()->count(50)->create([
        'portainer_id' => $portainer->id,
        'stack_name' => 'test-stack',
        'endpoint_name' => 'primary',
    ]);
    
    DB::enableQueryLog();
    
    Livewire::test(ContainersRelationManager::class, [
        'ownerRecord' => $portainer,
        'pageClass' => \App\Filament\Resources\Portainers\Pages\ViewPortainer::class,
    ]);
    
    $queries = DB::getQueryLog();
    $queryCount = count($queries);
    
    dump("ContainersRelationManager Query Count: $queryCount");
    foreach ($queries as $query) {
        $sql = $query['query'];
        if (str_contains($sql, 'select distinct')) {
            dump("Potential slow filter query: $sql");
        }
    }

    expect($queryCount)->toBeLessThan(10);
});
