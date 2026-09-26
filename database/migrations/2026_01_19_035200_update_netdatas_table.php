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
            $table->foreignId('cloudflare_domain_id')->nullable()->change();
            $table->string('ip')->nullable()->change();
            $table->integer('port')->nullable()->change();
            $table->foreignId('cloudflare_tunnel_id')->nullable()->constrained('cloudflare_tunnels')->nullOnDelete();
            $table->foreignId('cloudflare_dns_record_id')->nullable()->constrained('cloudflare_dns_records')->nullOnDelete();
            $table->string('domain')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('netdatas', function (Blueprint $table) {
            $table->dropForeign(['cloudflare_tunnel_id']);
            $table->dropColumn('cloudflare_tunnel_id');
            $table->dropForeign(['cloudflare_dns_record_id']);
            $table->dropColumn('cloudflare_dns_record_id');
            $table->dropColumn('domain');

            // Note: Reverting nullable columns to not nullable might fail if there are NULL values.
            $table->foreignId('cloudflare_domain_id')->nullable(false)->change();
            $table->string('ip')->nullable(false)->default('127.0.0.1')->change();
            $table->integer('port')->nullable(false)->default(19999)->change();
        });
    }
};
