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
        Schema::table('containers', function (Blueprint $table) {
            $table->index(['portainer_id', 'stack_name']);
            $table->index(['portainer_id', 'endpoint_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropIndex(['portainer_id', 'stack_name']);
            $table->dropIndex(['portainer_id', 'endpoint_name']);
        });
    }
};
