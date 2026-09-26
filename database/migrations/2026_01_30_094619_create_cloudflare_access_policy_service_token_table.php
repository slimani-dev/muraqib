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
        Schema::create('cloudflare_access_policy_service_token', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('policy_id')->constrained('cloudflare_access_policies')->cascadeOnDelete();
            $table->foreignUuid('service_token_id')->constrained('cloudflare_service_tokens')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_access_policy_service_token');
    }
};
