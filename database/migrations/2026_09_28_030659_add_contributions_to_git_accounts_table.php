<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('git_accounts', function (Blueprint $table) {
            $table->json('contributions')->nullable()->after('token');
            $table->timestamp('contributions_synced_at')->nullable()->after('contributions');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('git_accounts', function (Blueprint $table) {
            $table->dropColumn(['contributions', 'contributions_synced_at']);
        });
    }
};
