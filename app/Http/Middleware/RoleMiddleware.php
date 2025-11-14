<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * Usage:
     * ->middleware(['auth','role:admin'])
     * ->middleware(['auth','role:admin,faculty'])
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        // If not logged in, send to login
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Normalize roles to singular lowercase
        $normalizedRoles = array_map(fn($role) => strtolower(trim(rtrim($role, 's'))), $roles);

        // Normalize the user role too
        $userRole = strtolower(trim(rtrim($user->role, 's')));

        // If role matches → allow
        if (empty($normalizedRoles) || in_array($userRole, $normalizedRoles, true)) {
            return $next($request);
        }

        // Otherwise block
        abort(403, 'Unauthorized.');
    }
}
