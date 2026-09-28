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
        Schema::create('saved_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_account_id')->constrained()->cascadeOnDelete();
            $table->string('thread_id');
            $table->json('notification');
            $table->timestamps();

            $table->unique(['git_account_id', 'thread_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('saved_notifications');
    }
};
