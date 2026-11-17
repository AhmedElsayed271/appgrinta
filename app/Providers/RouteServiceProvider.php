<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        // Tell Laravel {locale} is never a model binding
        Route::pattern('locale', '[a-zA-Z]{2}(-[a-zA-Z]{2})?');

        $this->configureRateLimiting();

        $this->routes(function () {
            $this->mapApiRoutes();
            $this->mapDashboardRoutes();
            $this->mapWebRoutes();
        });
    }

    protected function mapApiRoutes(): void
    {
        Route::prefix('api/v1')
            ->middleware('api')
            ->group(base_path('routes/api/v1.php'));
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    protected function mapDashboardRoutes(): void
    {
        Route::middleware(['web'])
            ->group(base_path('routes/dashboard/web.php'));
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Dynamic-link claim is used by the iOS manual claim flow. A short
        // window limits code guessing without blocking a real first-run user.
        RateLimiter::for('deeplink-claim', function (Request $request) {
            return Limit::perMinute((int) config('deeplinks.claim_limit', 10))->by($request->ip());
        });

        RateLimiter::for('deeplink-create', function (Request $request) {
            return Limit::perMinute((int) config('deeplinks.create_limit', 30))->by($request->ip());
        });
    }
}
