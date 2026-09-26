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
        Schema::table('cloudflare_dns_records', function (Blueprint $table) {
            $table->integer('ttl')->default(1)->after('proxied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudflare_dns_records', function (Blueprint $table) {
            $table->dropColumn('ttl');
        });
    }
};
