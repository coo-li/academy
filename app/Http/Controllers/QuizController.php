<?php

namespace App\Http\Controllers;

use App\Models\Enrollment;
use App\Models\CareerLevel;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function show(Quiz $quiz)
    {
        $quiz->load(['module.careerLevel', 'module.method']);

        $enrollment = Enrollment::where('user_id', Auth::id())
            ->where('module_id', $quiz->module_id)
            ->first();

        if (! $enrollment || ! $enrollment->isQuizUnlocked()) {
            return redirect()->route('dashboard')
                ->with('error', 'Das Quiz ist erst nach bestätigter Teilnahme am Workshop freigeschaltet.');
        }

        $previousAttempts = QuizAttempt::where('user_id', Auth::id())
            ->where('quiz_id', $quiz->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('quiz.show', compact('quiz', 'previousAttempts'));
    }

    public function submit(Request $request, Quiz $quiz)
    {
        $enrollment = Enrollment::where('user_id', Auth::id())
            ->where('module_id', $quiz->module_id)
            ->first();

        if (! $enrollment || ! $enrollment->isQuizUnlocked()) {
            return redirect()->route('dashboard')
                ->with('error', 'Das Quiz ist erst nach bestätigter Teilnahme am Workshop freigeschaltet.');
        }

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $questions = $quiz->questions;
        $answers = $request->answers;
        $correct = 0;
        $total = count($questions);

        foreach ($questions as $index => $question) {
            $userAnswer = $answers[$index] ?? null;
            if ($userAnswer !== null && (int) $userAnswer === (int) $question['correct']) {
                $correct++;
            }
        }

        $score = $total > 0 ? round(($correct / $total) * 100) : 0;
        $passed = $score >= $quiz->pass_percentage;

        $attempt = QuizAttempt::create([
            'user_id' => Auth::id(),
            'quiz_id' => $quiz->id,
            'answers' => $answers,
            'score' => $score,
            'passed' => $passed,
        ]);

        if ($passed) {
            $this->completeModuleAndCheckLevel($quiz);
        }

        return view('quiz.result', compact('quiz', 'attempt', 'score', 'passed', 'correct', 'total'));
    }

    protected function completeModuleAndCheckLevel(Quiz $quiz): void
    {
        $userId = Auth::id();

        $enrollment = Enrollment::where('user_id', $userId)
            ->where('module_id', $quiz->module_id)
            ->first();

        if ($enrollment && $enrollment->status !== 'completed') {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
        }

        $user = Auth::user()->load('careerLevel');
        if (! $user->careerLevel) {
            return;
        }

        $mandatoryModuleIds = $user->careerLevel->modules()
            ->where('is_mandatory', true)
            ->pluck('id');

        $completedModuleIds = Enrollment::where('user_id', $userId)
            ->where('status', 'completed')
            ->whereIn('module_id', $mandatoryModuleIds)
            ->pluck('module_id');

        if ($mandatoryModuleIds->diff($completedModuleIds)->isEmpty()) {
            $nextLevel = CareerLevel::where('career_path_id', $user->careerLevel->career_path_id)
                ->where('level_number', $user->careerLevel->level_number + 1)
                ->first();

            if ($nextLevel) {
                $user->update(['career_level_id' => $nextLevel->id]);
            }
        }
    }
}
