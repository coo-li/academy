<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserNotArchived
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->isArchived()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')
                ->withErrors(['email' => 'Dein Zugang wurde deaktiviert. Bitte wende dich an einen Administrator.']);
        }

        return $next($request);
    }
}
