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
        Schema::create('repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('git_account_id')->constrained()->cascadeOnDelete();
            $table->string('owner');
            $table->string('name');
            $table->string('display_name')->nullable();
            $table->string('logo_url')->nullable();
            $table->boolean('is_managed')->default(false);
            $table->string('packagist_package')->nullable();
            $table->string('npm_package')->nullable();

            // Synced from the provider
            $table->text('description')->nullable();
            $table->string('html_url')->nullable();
            $table->string('default_branch')->nullable();
            $table->string('language')->nullable();
            $table->unsignedInteger('stars')->nullable();
            $table->unsignedInteger('forks')->nullable();
            $table->unsignedInteger('open_issues')->nullable();
            $table->unsignedInteger('open_pull_requests')->nullable();
            $table->timestamp('pushed_at')->nullable();
            $table->json('latest_release')->nullable();
            $table->json('ci')->nullable();
            $table->json('pull_requests')->nullable();
            $table->json('issues')->nullable();
            $table->json('downloads')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->text('sync_error')->nullable();

            $table->timestamps();

            $table->unique(['git_account_id', 'owner', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repositories');
    }
};
