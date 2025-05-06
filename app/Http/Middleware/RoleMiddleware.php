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
        // Check if user is logged in
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

        $user = Auth::user();

        // Check if user is inactive (status = 0)
        if ($user->status == 0) {
            Log::warning("Blocked inactive user ID {$user->id} from accessing {$request->path()} at " . now());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Your account is inactive. Contact support.'
                ], 403);
            }

            Auth::logout(); // Optionally log the user out
            return redirect('/')->withErrors(['errors' => 'Your account is inactive. Please contact support.']);
        }

        // Check if user has the required role
        if (!in_array((int) $user->role->id, array_map('intval', $roles))) {
            Log::info('Unauthorized role access by user ' . $user->id . ' to ' . $request->path() . ' at ' . now());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'You do not have permission to access this resource.'
                ], 403);
            }

            return redirect('/')->withErrors(['errors' => 'Unauthorized access.']);
        }

        Log::info('User ' . $user->id . ' accessed ' . $request->path() . ' at ' . now());
        return $next($request);
    }
}
