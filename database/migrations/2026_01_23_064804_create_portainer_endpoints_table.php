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
        Schema::create('portainer_endpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portainer_id')->constrained()->cascadeOnDelete();
            $table->integer('endpoint_id'); // External ID from Portainer
            $table->string('name');
            $table->string('url')->nullable();
            $table->integer('type')->default(1);
            $table->integer('status')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portainer_endpoints');
    }
};
