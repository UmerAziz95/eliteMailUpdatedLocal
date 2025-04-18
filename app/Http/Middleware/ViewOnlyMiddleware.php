<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ViewOnlyMiddleware
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
        if (Auth::check() && Auth::user()->role_id === 5) {
            // Allow only GET requests for view-only users
            if (!$request->isMethod('get')) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You have view-only access. Modifications are not allowed.'
                    ], 403);
                }
                // withErrors
                return back()->with('error', 'You have view-only access. Modifications are not allowed.');
            }
        }

        return $next($request);
    }
}