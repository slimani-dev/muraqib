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
        Schema::create('cloudflare_access_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('account_id')->constrained('cloudflares')->cascadeOnDelete();
            $table->string('app_id')->unique()->comment('Cloudflare App UUID');
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('type')->default('self_hosted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_access_applications');
    }
};
