<?php

namespace App\Listeners;

use App\Services\PersonioService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;

class AssignCareerPathOnLogin
{
    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user->career_level_id || ! $user->personio_position) {
            return;
        }

        $personioService = app(PersonioService::class);

        if ($personioService->applyCareerMapping($user)) {
            Log::info('Personio: Career path auto-assigned on login.', [
                'user_id' => $user->id,
                'position' => $user->personio_position,
                'career_level_id' => $user->fresh()->career_level_id,
            ]);
        }
    }
}
