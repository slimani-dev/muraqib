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
        Schema::create('media_services', function (Blueprint $table) {
            $table->id();
            $table->string('type')->unique();
            $table->string('name');
            $table->string('url');
            $table->text('api_key')->nullable();
            $table->string('username')->nullable();
            $table->text('password')->nullable();
            $table->json('settings')->nullable();
            $table->string('status_url')->nullable();
            $table->text('status_headers')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_services');
    }
};
