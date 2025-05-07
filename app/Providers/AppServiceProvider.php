<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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
        Relation::morphMap([
            'employe' => \App\Models\Employe::class,
            'grh' => \App\Models\Grh::class,
            'admin' => \App\Models\Admin::class,
        ]);
    }
}
