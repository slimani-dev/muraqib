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
        Schema::create('stacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portainer_id')->constrained()->cascadeOnDelete();
            $table->string('external_id')->unique(); // Portainer stack ID
            $table->string('name');
            $table->integer('endpoint_id');
            $table->integer('stack_status')->nullable();
            $table->integer('stack_type')->nullable();
            $table->timestamp('created_at_portainer')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stacks');
    }
};
