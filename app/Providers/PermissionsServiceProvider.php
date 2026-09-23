<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class PermissionsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Blade::directive('permission', function ($permissions) {
            return "<?php if(auth()->check() && auth()->user()->hasPermission($permissions)) : ?>"; //return this if statement inside php tag
        });

        Blade::directive('endpermission', function ($permissions) {
            return "<?php endif; ?>"; //return this endif statement inside php tag
        });
    }
}
