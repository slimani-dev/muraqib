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
        Schema::table('stacks', function (Blueprint $table) {
            $table->foreignId('portainer_endpoint_id')->nullable()->after('portainer_id')->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stacks', function (Blueprint $table) {
            $table->dropForeign(['portainer_endpoint_id']);
            $table->dropColumn('portainer_endpoint_id');
        });
    }
};
