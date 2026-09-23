<?php

namespace App\Http\Middleware;

use Closure;

class PermissionMiddleware{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$permissions)
    {
        foreach ($permissions as $permission) {
            if(auth()->user()->hasPermission($permission)) {
                return $next($request);
            }
        }

        session()->flash('error', 'You don\'t have permission to access this route');
        return redirect()->back();
    }
}
