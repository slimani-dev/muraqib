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
        Schema::table('netdatas', function (Blueprint $table) {
            $table->dropForeign(['cloudflare_tunnel_id']);
            $table->dropColumn('cloudflare_tunnel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('netdatas', function (Blueprint $table) {
            $table->foreignId('cloudflare_tunnel_id')->nullable()->constrained('cloudflare_tunnels')->nullOnDelete();
        });
    }
};
