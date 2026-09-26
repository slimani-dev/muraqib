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
        Schema::create('containers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portainer_id')->constrained()->cascadeOnDelete();
            $table->string('container_id')->unique(); // Docker container ID
            $table->string('name');
            $table->string('image');
            $table->string('state', 50);
            $table->string('status')->nullable();
            $table->string('stack_name')->nullable();
            $table->timestamp('created_at_portainer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
