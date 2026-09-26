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
            $table->dropForeign(['cloudflare_dns_record_id']);
            $table->dropColumn('cloudflare_dns_record_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('netdatas', function (Blueprint $table) {
            $table->foreignId('cloudflare_dns_record_id')->nullable()->constrained('cloudflare_dns_records')->nullOnDelete();
        });
    }
};
