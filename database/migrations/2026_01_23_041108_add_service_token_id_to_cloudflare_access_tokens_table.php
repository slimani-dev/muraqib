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
        Schema::table('cloudflare_access_tokens', function (Blueprint $table) {
            $table->string('service_token_id')->nullable()->after('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudflare_access_tokens', function (Blueprint $table) {
            $table->dropColumn('service_token_id');
        });
    }
};
