<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Route protection for RBAC. Usage in routes: ->middleware('role:admin')
 *
 * If the signed-in user has the wrong role we send them to THEIR own
 * dashboard (friendlier than a bare 403 page).
 */
class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! in_array($user->role?->value, $roles, true)) {
            return redirect($user->role->homePath());
        }

        return $next($request);
    }
}
