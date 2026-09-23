<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Share site settings with all views
        $settings = Setting::all()->first();
        View::share('site_settings', $settings);

        // Set default locale parameter for all URLs
        URL::defaults([
            'locale' => app()->getLocale()
        ]);
    }
}