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
        Schema::create('git_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('provider');
            $table->string('name');
            $table->string('base_url')->nullable();
            $table->string('username')->nullable();
            $table->text('token')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('git_accounts');
    }
};
