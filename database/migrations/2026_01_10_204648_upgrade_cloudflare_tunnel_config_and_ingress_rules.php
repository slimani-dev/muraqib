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
        Schema::table('cloudflare_tunnels', function (Blueprint $table) {
            $table->string('description')->nullable()->after('name');
            $table->string('loglevel')->default('info')->after('status');
            $table->string('transport_loglevel')->default('warn')->after('loglevel');
            $table->string('protocol')->default('auto')->after('transport_loglevel');
            $table->boolean('proxy_dns')->default(false)->after('protocol');
            $table->integer('proxy_dns_port')->default(53)->after('proxy_dns');
            $table->json('proxy_dns_upstream')->nullable()->after('proxy_dns_port');
            $table->timestamp('conns_active_at')->nullable()->after('updated_at');
            $table->timestamp('conns_inactive_at')->nullable()->after('conns_active_at');
            $table->string('client_version')->nullable()->after('conns_inactive_at');
            $table->boolean('remote_config')->default(true)->after('client_version');
        });

        Schema::table('cloudflare_ingress_rules', function (Blueprint $table) {
            $table->string('hostname')->nullable()->change();
            $table->string('path')->nullable()->after('hostname');
            $table->boolean('is_catch_all')->default(false)->after('service');
            $table->json('origin_request')->nullable()->after('is_catch_all');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cloudflare_tunnels', function (Blueprint $table) {
            $table->dropColumn([
                'description',
                'loglevel',
                'transport_loglevel',
                'protocol',
                'proxy_dns',
                'proxy_dns_port',
                'proxy_dns_upstream',
                'conns_active_at',
                'conns_inactive_at',
                'client_version',
                'remote_config',
            ]);
        });

        Schema::table('cloudflare_ingress_rules', function (Blueprint $table) {
            // Revert changes - this is best effort as nullable status cannot always be easily reverted without data loss risk
            $table->string('hostname')->nullable(false)->change();
            $table->dropColumn(['path', 'is_catch_all', 'origin_request']);
        });
    }
};
