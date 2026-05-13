<?php

namespace App\Http\Controllers;

use App\Models\CareerLevel;
use App\Models\Enrollment;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizEvaluationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class QuizController extends Controller
{
    public function __construct(
        protected QuizEvaluationService $evaluator
    ) {}

    public function show(Quiz $quiz)
    {
        $quiz->load(['module.careerLevel', 'module.method']);

        $enrollment = Enrollment::where('user_id', Auth::id())
            ->where('module_id', $quiz->module_id)
            ->first();

        if (! $enrollment) {
            return redirect()->route('dashboard')
                ->with('error', 'Du musst dich zuerst für dieses Modul einschreiben.');
        }

        if (! $enrollment->isQuizUnlocked()) {
            return redirect()->route('dashboard')
                ->with('error', 'Das Quiz ist erst nach bestätigter Teilnahme freigeschaltet.');
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
                ->with('error', 'Das Quiz ist erst nach bestätigter Teilnahme freigeschaltet.');
        }

        $request->validate([
            'answers' => ['required', 'array'],
        ]);

        $result = $this->evaluator->evaluate($quiz, $request->answers);

        $attempt = QuizAttempt::create([
            'user_id' => Auth::id(),
            'quiz_id' => $quiz->id,
            'answers' => $request->answers,
            'score' => $result['score'],
            'passed' => $result['passed'],
        ]);

        if ($result['passed']) {
            $this->completeModuleAndCheckLevel($quiz);
        }

        return view('quiz.result', [
            'quiz' => $quiz,
            'attempt' => $attempt,
            'score' => $result['score'],
            'passed' => $result['passed'],
            'correct' => $result['correct'],
            'total' => $result['total'],
            'details' => $result['details'],
        ]);
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

        $user = Auth::user()->load('careerLevels');
        if ($user->careerLevels->isEmpty()) {
            return;
        }

        foreach ($user->careerLevels as $level) {
            $mandatoryIds = $level->modules()->where('is_mandatory', true)->pluck('id');
            $completedIds = Enrollment::where('user_id', $userId)
                ->where('status', 'completed')
                ->whereIn('module_id', $mandatoryIds)
                ->pluck('module_id');

            if ($mandatoryIds->isNotEmpty() && $mandatoryIds->diff($completedIds)->isEmpty()) {
                $nextLevel = CareerLevel::where('career_path_id', $level->career_path_id)
                    ->where('level_number', $level->level_number + 1)
                    ->first();

                if ($nextLevel) {
                    $user->replaceCareerLevel($level, $nextLevel);
                }
            }
        }
    }
}
