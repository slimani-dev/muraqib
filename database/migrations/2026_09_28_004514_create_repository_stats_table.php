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
        Schema::create('repository_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repository_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('stars')->nullable();
            $table->unsignedInteger('forks')->nullable();
            $table->unsignedBigInteger('downloads')->nullable();
            $table->timestamps();

            $table->unique(['repository_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repository_stats');
    }
};
