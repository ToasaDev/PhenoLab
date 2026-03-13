<?php

namespace App\Providers;

use App\Models\Observation;
use App\Models\Plant;
use App\Models\Site;
use App\Observers\ObservationObserver;
use App\Observers\PlantObserver;
use App\Observers\SiteObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
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

        // ── Rate Limiters ─────────────────────────────────────
        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        RateLimiter::for('api', fn ($request) => $request->user()
            ? Limit::perMinute(120)->by($request->user()->id)
            : Limit::perMinute(30)->by($request->ip())
        );

        // ── Access Control Gates ─────────────────────────────
        Gate::define('staff', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('manage-categories', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('manage-stages', fn ($user) => $user->is_staff || $user->is_superuser);
        Gate::define('view-analyses', fn ($user) => true);
        Gate::define('view-activity', fn ($user) => true);
    }
}
