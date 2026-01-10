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
        Schema::create('cloudflare_ingress_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_tunnel_id')->constrained('cloudflare_tunnels')->cascadeOnDelete();
            $table->string('hostname')->nullable(); // e.g. sub.example.com
            $table->string('service')->nullable(); // e.g. http://localhost:8080
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_ingress_rules');
    }
};
