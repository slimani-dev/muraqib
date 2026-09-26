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
        Schema::create('cloudflare_transform_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->foreignId('cloudflare_id')->constrained()->cascadeOnDelete();
            $table->text('pattern')->nullable(); // URL/domain regex pattern
            $table->json('headers')->nullable(); // Custom headers to inject
            $table->json('rule_ids')->nullable(); // Array of deployed Rule IDs
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_transform_rules');
    }
};
