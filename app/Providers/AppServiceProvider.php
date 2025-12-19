<?php

namespace App\Providers;

use Modules\Catalogue\Models\Brand;
use Illuminate\Support\ServiceProvider;
use Modules\Catalogue\Observers\BrandObserver;

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
        Brand::observe(BrandObserver::class);
    }
}
