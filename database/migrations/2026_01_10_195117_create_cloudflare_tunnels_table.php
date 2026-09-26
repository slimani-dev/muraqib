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
        Schema::create('cloudflare_tunnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_id')->constrained('cloudflares')->cascadeOnDelete();
            $table->uuid('tunnel_id')->nullable(); // Cloudflare Tunnel UUID
            $table->string('name')->nullable();
            $table->text('token')->nullable(); // Encrypted
            $table->boolean('is_active')->default(false); // Indicates if this tunnel is "selected" or locally active
            $table->string('status')->default('inactive'); // Service status (active, inactive, degraded)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_tunnels');
    }
};
