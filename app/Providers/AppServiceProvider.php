<?php

namespace App\Providers;

use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use App\Observers\ObservationObserver;
use App\Observers\PlantObserver;
use App\Observers\SiteObserver;
use Illuminate\Support\Facades\Gate;
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

        // ── Access Control Gates ─────────────────────────────
        Gate::define('staff', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('manage-categories', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('manage-stages', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('view-analyses', fn ($user) => true);
        Gate::define('view-activity', fn ($user) => true);
    }
}
