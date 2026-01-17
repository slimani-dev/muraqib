<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stacks', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('stack_type');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('stacks', function (Blueprint $table) {
            $table->dropColumn('icon');
        });

        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
