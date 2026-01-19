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
        Schema::create('netdatas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloudflare_domain_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cloudflare_access_id')->nullable()->constrained('cloudflare_access_tokens')->nullOnDelete();
            $table->string('name')->unique();
            $table->string('ip')->default('127.0.0.1');
            $table->integer('port')->default(19999);
            $table->string('status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('netdatas');
    }
};
