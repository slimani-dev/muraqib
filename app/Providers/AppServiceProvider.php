<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
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
        Model::automaticallyEagerLoadRelationships();

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        CreateAction::configureUsing(function (Action $action) {
            $action->slideOver()->modalWidth('3xl');
        });

        EditAction::configureUsing(function (Action $action) {
            $action->slideOver()->modalWidth('3xl');
        });

        Schema::configureUsing(function (Schema $schema) {
            $schema->columns(1);
        });

        Table::configureUsing(function (Table $table) {
            $table->filtersLayout(FiltersLayout::AboveContent)
                ->deferFilters(false)
                ->persistFiltersInSession()
                ->persistSearchInSession()
                ->persistColumnSearchesInSession();;
        });

//        \Illuminate\Support\Facades\DB::listen(function ($query) {
//            \Illuminate\Support\Facades\Log::info($query->sql, $query->bindings);
//            \Illuminate\Support\Facades\Log::info($query->time . 'ms');
//        });


    }
}
