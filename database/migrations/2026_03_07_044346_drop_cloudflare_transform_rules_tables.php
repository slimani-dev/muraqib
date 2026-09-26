<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cloudflare_transform_ruleables');
        Schema::dropIfExists('cloudflare_transform_rules');
    }

    public function down(): void
    {
        // These tables were permanently removed as part of feature deletion.
    }
};
