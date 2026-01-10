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
        Schema::create('cloudflares', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Friendly name
            $table->string('account_id')->nullable();
            $table->text('api_token')->nullable(); // Encrypted
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflares');
    }
};
