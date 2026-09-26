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
        Schema::create('cloudflare_dns_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_domain_id')->constrained('cloudflare_domains')->cascadeOnDelete();
            $table->string('record_id')->nullable(); // Cloudflare Record ID
            $table->string('type')->default('CNAME'); // CNAME, A, AAAA, etc.
            $table->string('name')->nullable(); // subdomain
            $table->string('content')->nullable(); // target
            $table->boolean('proxied')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_dns_records');
    }
};
