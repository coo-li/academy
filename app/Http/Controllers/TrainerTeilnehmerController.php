<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerTeilnehmerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $moduleIds = $this->trainerModuleIds($user);

        $sessions = TrainingSession::with(['module', 'enrollments.user'])
            ->whereIn('module_id', $moduleIds)
            ->orderBy('start_at', 'desc')
            ->paginate(20);

        return view('trainer.teilnehmer', compact('sessions'));
    }

    public function confirmAttendance(TrainingSession $session, Request $request)
    {
        $this->authorizeSession($session);

        $request->validate([
            'attendees' => ['required', 'array', 'min:1'],
            'attendees.*' => ['integer', 'exists:users,id'],
        ]);

        $confirmed = Enrollment::where('training_session_id', $session->id)
            ->where('status', 'enrolled')
            ->whereIn('user_id', $request->attendees)
            ->get();

        foreach ($confirmed as $enrollment) {
            $enrollment->update([
                'status' => 'attended',
                'attendance_confirmed_at' => now(),
                'attendance_confirmed_by' => Auth::id(),
            ]);
        }

        $count = $confirmed->count();

        return back()->with('success', "Anwesenheit für {$count} Teilnehmer bestätigt.");
    }

    private function trainerModuleIds($user): array
    {
        if ($user->isAdmin()) {
            return Module::pluck('id')->toArray();
        }

        return Module::where('accountable_type', 'user')
            ->where('accountable_user_id', $user->id)
            ->pluck('id')
            ->toArray();
    }

    private function authorizeSession(TrainingSession $session): void
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return;
        }

        $module = $session->module;

        if ($module->accountable_type !== 'user' || $module->accountable_user_id !== $user->id) {
            abort(403, 'Du bist nicht als Trainer für dieses Modul eingetragen.');
        }
    }
}
