<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {   
        // If user is not logged in
        if (!Auth::check()) {
            Log::info('Unauthorized access attempt to ' . $request->path() . ' at ' . now());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Unauthorized. Please log in.'
                ], 401);
            }
            
            return redirect('/')->withErrors(['errors' => 'You must be logged in to access this page.']);
        }

        // If user is logged in but does not have the required role
        if (!in_array((int) Auth::user()->role->id, array_map('intval', $roles))) {
            Log::info('Unauthorized access attempt by user ' . Auth::user()->id . ' to ' . $request->path() . ' at ' . now());
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'You do not have permission to access this resource.'
                ], 403);
            }
            
            return redirect('/')->withErrors(['errors' => 'Unauthorized access.']);
        }

        Log::info('User ' . Auth::user()->id . ' accessed ' . $request->path() . ' at ' . now());
        return $next($request);
    }
}
