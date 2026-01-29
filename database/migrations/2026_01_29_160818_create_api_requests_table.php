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
        Schema::create('api_requests', function (Blueprint $table) {
            $table->id();
            $table->string('service')->index(); // e.g., Cloudflare, Portainer
            $table->string('name')->nullable(); // e.g., "listTunnels"
            $table->string('method');
            $table->text('url');

            $table->json('request_headers')->nullable();
            $table->longText('request_body')->nullable();

            $table->integer('status_code')->nullable();
            $table->json('response_headers')->nullable();
            $table->longText('response_body')->nullable();

            $table->integer('duration_ms')->nullable();
            $table->text('error')->nullable();

            // Metadata
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->json('meta')->nullable(); // Extra context

            $table->timestamps();

            $table->index(['service', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_requests');
    }
};
