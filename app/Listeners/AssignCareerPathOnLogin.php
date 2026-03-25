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

        // #region agent log
        @file_put_contents('/root/.cursor/debug-562df2.log', json_encode(['sessionId' => '562df2', 'hypothesisId' => 'C', 'location' => 'AssignCareerPathOnLogin:handle', 'message' => 'login_event_fired', 'data' => ['user' => $user->name, 'level_raw' => $user->personio_level_raw, 'position' => $user->personio_position], 'timestamp' => round(microtime(true) * 1000)]) . "\n", FILE_APPEND);
        // #endregion

        if (! $user->personio_position) {
            return;
        }

        $user->load('careerLevels');
        $personioService = app(PersonioService::class);

        $applied = $personioService->applyCareerMapping($user);
        $autoMatched = $personioService->tryAutoMatch($user);

        if ($applied || $autoMatched) {
            Log::info('Personio: Karrierepfad(e) auto-assigned on login.', [
                'user_id' => $user->id,
                'position' => $user->personio_position,
                'career_levels' => $user->fresh()->careerLevels->pluck('id')->all(),
            ]);
        }
    }
}
