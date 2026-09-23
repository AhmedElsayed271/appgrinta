<?php

namespace App\Http\Middleware;

use Closure;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next, ...$roles)
    {
        foreach ($roles as $role) {
            if(auth()->user()->hasRole($role)) {
                return $next($request);
            }
        }

        session()->flash('error', 'You don\'t have permission to access this route');
        return redirect()->back();
    }
}
