<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Give every existing team its default dashboard page (new teams get one when they're created).
     */
    public function up(): void
    {
        $teamsWithout = DB::table('teams')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('dashboard_pages')
                ->whereColumn('dashboard_pages.team_id', 'teams.id')
                ->where('dashboard_pages.is_default', true))
            ->pluck('id');

        foreach ($teamsWithout as $teamId) {
            DB::table('dashboard_pages')->insert([
                'team_id' => $teamId,
                'name' => 'Dashboard',
                'slug' => 'dashboard',
                'is_default' => true,
                'position' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
