<?php

namespace App\Services;

use App\Models\Module;
use App\Models\ModuleInterest;
use App\Models\User;
use Illuminate\Support\Collection;

class ModuleInterestService
{
    public function __construct(
        protected AsanaService $asana
    ) {}

    public function express(User $user, Module $module): ModuleInterest
    {
        $interest = ModuleInterest::firstOrCreate([
            'user_id' => $user->id,
            'module_id' => $module->id,
        ]);

        if ($interest->wasRecentlyCreated && $this->asana->isConfigured()) {
            $assigneeEmail = $user->getPeopleManager()?->email;
            $task = $this->asana->createInterestTask($user, $module, $assigneeEmail);
            if ($task) {
                $interest->update(['asana_task_gid' => $task['gid'] ?? null]);
            }
        }

        return $interest;
    }

    public function withdraw(User $user, Module $module): bool
    {
        $interest = ModuleInterest::where('user_id', $user->id)
            ->where('module_id', $module->id)
            ->first();

        if (! $interest) {
            return false;
        }

        if ($interest->asana_task_gid && $this->asana->isConfigured()) {
            $this->asana->addComment(
                $interest->asana_task_gid,
                "ℹ️ Interesse zurückgezogen von {$user->name} am " . now()->format('d.m.Y, H:i') . " Uhr."
            );
            $this->asana->completeTask($interest->asana_task_gid);
        }

        return $interest->delete();
    }

    public function markAsNoted(ModuleInterest $interest, User $manager): void
    {
        $interest->update([
            'noted_at' => now(),
            'noted_by' => $manager->id,
        ]);

        if ($interest->asana_task_gid && $this->asana->isConfigured()) {
            $this->asana->completeTask($interest->asana_task_gid);
        }
    }

    /**
     * Get all pending interests for employees managed by the given manager.
     */
    public function getForManager(User $manager): Collection
    {
        $employeeIds = $manager->managedEmployees()->pluck('users.id');

        return ModuleInterest::with(['user', 'module.careerLevel.careerPath'])
            ->whereIn('user_id', $employeeIds)
            ->whereNull('noted_at')
            ->orderByDesc('created_at')
            ->get();
    }
}
