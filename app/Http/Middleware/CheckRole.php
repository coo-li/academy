<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        $userRole = $request->user()->role ?? 'employee';

        if (in_array('admin', $roles) && $request->user()->isAdmin()) {
            return $next($request);
        }

        if (in_array('c_level', $roles) && $request->user()->hasAdminAccess()) {
            return $next($request);
        }

        if (in_array('people_manager', $roles) && $request->user()->hasPeopleManagerAccess()) {
            return $next($request);
        }

        if (in_array($userRole, $roles)) {
            return $next($request);
        }

        abort(403, 'Keine Berechtigung für diesen Bereich.');
    }
}
