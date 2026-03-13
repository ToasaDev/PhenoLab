<?php

namespace App\Providers;

use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use App\Observers\ObservationObserver;
use App\Observers\PlantObserver;
use App\Observers\SiteObserver;
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
        Observation::observe(ObservationObserver::class);
        Plant::observe(PlantObserver::class);
        Site::observe(SiteObserver::class);
    }
}
