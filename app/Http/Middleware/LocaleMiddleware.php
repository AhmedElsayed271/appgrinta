<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;

class LocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locales = (array) config('translatable.locales');
        if (in_array($request->input('locale'), $locales)) {
            App::setLocale($request->input('locale'));
        }

        // Ensure URL generation includes the current locale when routes use {locale}
        URL::defaults(['locale' => App::getLocale()]);

        return $next($request);
    }
}
