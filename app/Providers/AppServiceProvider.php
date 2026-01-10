<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Action::configureUsing(function (Action $action) {
            $action->slideOver()->modalWidth('3xl');
        });

        Schema::configureUsing(function (Schema $schema) {
            $schema->columns(1);
        });
    }
}
