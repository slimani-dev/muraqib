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
            $table->string('latest_tag')->nullable()->after('update_checked_at');
            $table->string('latest_digest')->nullable()->after('latest_tag');
            $table->text('update_error')->nullable()->after('latest_digest');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn(['latest_tag', 'latest_digest', 'update_error']);
        });
    }
};
