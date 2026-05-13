<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\Module;
use App\Models\TrainingSession;
use App\Notifications\EnrollmentStatusChanged;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TrainerTeilnehmerController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $moduleIds = $this->trainerModuleIds($user);

        $sessions = TrainingSession::with(['module.method', 'enrollments.user'])
            ->whereIn('module_id', $moduleIds)
            ->orderBy('start_at', 'desc')
            ->paginate(20);

        $selfStudyEnrollments = Enrollment::with(['user', 'module.method'])
            ->whereIn('module_id', $moduleIds)
            ->whereHas('module.method', fn ($q) => $q->where('scheduling_type', 'self_study'))
            ->whereIn('status', ['enrolled', 'attended', 'completed'])
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('module_id');

        $selfStudyModules = Module::with('method')
            ->whereIn('id', $selfStudyEnrollments->keys())
            ->orderBy('title')
            ->get();

        return view('trainer.teilnehmer', compact('sessions', 'selfStudyEnrollments', 'selfStudyModules'));
    }

    public function confirmAttendance(TrainingSession $session, Request $request)
    {
        $this->authorizeSession($session);

        if ($session->end_at->isFuture()) {
            return back()->with('error', 'Die Anwesenheit kann erst nach Ende des Termins bestätigt werden.');
        }

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

            $enrollment->load('module');
            $enrollment->user->notify(new EnrollmentStatusChanged($enrollment));
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
