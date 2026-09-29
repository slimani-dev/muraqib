<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cloudflare Access protection for a subdomain (App\Models\CloudflareAccess). This migration
 * was missing, so fresh installs had no table; existing installs already have it and skip it.
 * It must run before netdatas, which references it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cloudflare_access_tokens')) {
            return;
        }

        Schema::create('cloudflare_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_domain_id')->constrained()->cascadeOnDelete();
            $table->string('app_id')->nullable();
            $table->string('name');
            $table->string('client_id')->nullable();
            $table->string('service_token_id')->nullable();
            $table->text('client_secret')->nullable();
            $table->string('policy_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cloudflare_access_tokens');
    }
};
