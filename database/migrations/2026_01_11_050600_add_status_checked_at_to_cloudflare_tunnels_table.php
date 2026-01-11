<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cloudflare_tunnels', function (Blueprint $table) {
            $table->timestamp('status_checked_at')->nullable()->after('conns_inactive_at');
        });
    }

    public function down(): void
    {
        Schema::table('cloudflare_tunnels', function (Blueprint $table) {
            $table->dropColumn('status_checked_at');
        });
    }
};
