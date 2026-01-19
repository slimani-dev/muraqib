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
        Schema::table('netdatas', function (Blueprint $table) {
            $table->dropColumn(['domain', 'ip', 'port']);
            $table->foreignId('cloudflare_ingress_rule_id')->nullable()->constrained('cloudflare_ingress_rules')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('netdatas', function (Blueprint $table) {
            $table->dropForeign(['cloudflare_ingress_rule_id']);
            $table->dropColumn('cloudflare_ingress_rule_id');
            $table->string('domain')->nullable();
            $table->string('ip')->nullable();
            $table->integer('port')->nullable();
        });
    }
};
