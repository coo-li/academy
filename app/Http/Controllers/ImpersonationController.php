<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating another user.
     */
    public function start(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        
        // Check if user can impersonate the target
        if (!$currentUser->canImpersonate($user)) {
            abort(403, 'Du darfst diesen Nutzer nicht impersonieren.');
        }
        
        // Store original user ID in session
        session()->put('impersonating_from', $currentUser->id);
        session()->put('impersonating_from_name', $currentUser->name);
        
        // Login as target user
        Auth::loginUsingId($user->id);
        
        return redirect()->route('dashboard')
            ->with('message', "Du siehst die Anwendung jetzt als {$user->name}");
    }

    /**
     * Stop impersonating and return to original account.
     */
    public function stop(): RedirectResponse
    {
        $originalUserId = session()->pull('impersonating_from');
        session()->forget('impersonating_from_name');
        
        if (!$originalUserId) {
            return redirect()->route('dashboard');
        }
        
        // Login back as original user
        Auth::loginUsingId($originalUserId);
        
        return redirect()->route('admin.dashboard.team')
            ->with('message', 'Du bist wieder in deinem Account.');
    }
}
