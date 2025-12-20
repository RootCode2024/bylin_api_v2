<?php

namespace App\Providers;

use Modules\Catalogue\Models\Brand;
use Modules\Catalogue\Models\Category;
use Illuminate\Support\ServiceProvider;
use Modules\Catalogue\Observers\BrandObserver;
use Modules\Catalogue\Observers\CategoryObserver;

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
        Category::observe(CategoryObserver::class);
    }
}
