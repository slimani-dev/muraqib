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
        Schema::create('cloudflare_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_domain_id')->constrained()->cascadeOnDelete(); // Zone link
            $table->string('app_id')->nullable();       // Cloudflare Application UUID
            $table->string('name');                    // Subdomain protected (e.g. netdata.slimani.dev)
            $table->string('client_id');               // Service Token Client ID
            $table->text('client_secret')->nullable(); // Encrypted Secret (Nullable for imported tokens)
            $table->string('policy_id')->nullable();   // UUID for the Service Auth policy
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_access_tokens');
    }
};
