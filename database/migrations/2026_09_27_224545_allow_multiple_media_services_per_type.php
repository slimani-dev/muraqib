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
        Schema::table('media_services', function (Blueprint $table) {
            $table->dropUnique(['type']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_services', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->unique('type');
        });
    }
};
