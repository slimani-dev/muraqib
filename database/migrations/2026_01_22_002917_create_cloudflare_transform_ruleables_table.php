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
        Schema::create('cloudflare_transform_ruleables', function (Blueprint $table) {
            $table->string('cloudflare_transform_rule_id', 26);
            $table->foreign('cloudflare_transform_rule_id', 'ctr_rule_fk')
                ->references('id')
                ->on('cloudflare_transform_rules')
                ->cascadeOnDelete();
            $table->string('cloudflare_transform_ruleable_type');
            $table->unsignedBigInteger('cloudflare_transform_ruleable_id');
            $table->timestamps();

            $table->index(['cloudflare_transform_ruleable_type', 'cloudflare_transform_ruleable_id'], 'ctr_poly_idx');
            $table->primary(['cloudflare_transform_rule_id', 'cloudflare_transform_ruleable_id', 'cloudflare_transform_ruleable_type'], 'ctr_poly_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cloudflare_transform_ruleables');
    }
};
