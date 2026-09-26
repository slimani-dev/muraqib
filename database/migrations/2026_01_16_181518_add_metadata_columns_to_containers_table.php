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
            $table->string('display_name')->nullable()->after('name');
            $table->string('url')->nullable()->after('display_name');
            $table->text('description')->nullable()->after('url');
            $table->boolean('is_main')->default(false)->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn(['display_name', 'url', 'description', 'is_main']);
        });
    }
};
