<?php

namespace App\Services;

use App\Models\Quiz;

class QuizEvaluationService
{
    /**
     * Evaluate all answers against a quiz's questions.
     *
     * @return array{score: int, passed: bool, correct: int, total: int, details: array}
     */
    public function evaluate(Quiz $quiz, array $answers): array
    {
        $questions = $quiz->questions;
        $total = count($questions);
        $correct = 0;
        $details = [];

        foreach ($questions as $index => $question) {
            $type = $question['type'] ?? 'single_choice';
            $userAnswer = $answers[$index] ?? null;
            $isCorrect = $this->evaluateQuestion($type, $question, $userAnswer);

            if ($isCorrect) {
                $correct++;
            }

            $details[$index] = [
                'type' => $type,
                'correct' => $isCorrect,
                'user_answer' => $userAnswer,
                'expected' => $this->getExpectedAnswer($type, $question),
            ];
        }

        $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

        return [
            'score' => $score,
            'passed' => $score >= $quiz->pass_percentage,
            'correct' => $correct,
            'total' => $total,
            'details' => $details,
        ];
    }

    protected function evaluateQuestion(string $type, array $question, mixed $userAnswer): bool
    {
        return match ($type) {
            'single_choice' => $this->evaluateSingleChoice($question, $userAnswer),
            'multiple_choice' => $this->evaluateMultipleChoice($question, $userAnswer),
            'true_false' => $this->evaluateTrueFalse($question, $userAnswer),
            'short_answer' => $this->evaluateShortAnswer($question, $userAnswer),
            'ordering' => $this->evaluateOrdering($question, $userAnswer),
            'matching' => $this->evaluateMatching($question, $userAnswer),
            'cloze' => $this->evaluateCloze($question, $userAnswer),
            default => false,
        };
    }

    protected function evaluateSingleChoice(array $question, mixed $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        return (int) $answer === (int) $question['correct'];
    }

    /**
     * All-or-nothing: user must select exactly all correct options and no wrong ones.
     */
    protected function evaluateMultipleChoice(array $question, mixed $answer): bool
    {
        if (! is_array($answer)) {
            return false;
        }

        $selected = collect($answer)->map(fn ($v) => (int) $v)->sort()->values()->all();
        $expected = collect($question['correct'])->map(fn ($v) => (int) $v)->sort()->values()->all();

        return $selected === $expected;
    }

    protected function evaluateTrueFalse(array $question, mixed $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        $userBool = filter_var($answer, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $correctBool = (bool) $question['correct'];

        return $userBool === $correctBool;
    }

    /**
     * Case-insensitive, trimmed comparison against a list of accepted answers.
     * Allows Levenshtein distance of 1 for words longer than 4 characters.
     */
    protected function evaluateShortAnswer(array $question, mixed $answer): bool
    {
        if ($answer === null || trim((string) $answer) === '') {
            return false;
        }

        $userAnswer = mb_strtolower(trim((string) $answer));
        $accepted = $question['accepted_answers'] ?? [];

        foreach ($accepted as $valid) {
            $normalizedValid = mb_strtolower(trim($valid));

            if ($userAnswer === $normalizedValid) {
                return true;
            }

            if (mb_strlen($normalizedValid) > 4 && levenshtein($userAnswer, $normalizedValid) <= 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * User must place all items in the exact correct order.
     */
    protected function evaluateOrdering(array $question, mixed $answer): bool
    {
        if (! is_array($answer)) {
            return false;
        }

        $userOrder = array_map('intval', $answer);
        $correctOrder = array_map('intval', $question['correct_order']);

        return $userOrder === $correctOrder;
    }

    /**
     * User must match all pairs correctly (left index -> right index).
     */
    protected function evaluateMatching(array $question, mixed $answer): bool
    {
        if (! is_array($answer)) {
            return false;
        }

        $expected = $question['correct_pairs'] ?? [];

        foreach ($expected as $leftIdx => $rightIdx) {
            if (! isset($answer[$leftIdx]) || (int) $answer[$leftIdx] !== (int) $rightIdx) {
                return false;
            }
        }

        return count($answer) === count($expected);
    }

    /**
     * Each blank must match one of its accepted answers (case-insensitive, trimmed).
     */
    protected function evaluateCloze(array $question, mixed $answer): bool
    {
        if (! is_array($answer)) {
            return false;
        }

        $blanks = $question['blanks'] ?? [];

        foreach ($blanks as $blankIdx => $blank) {
            $userValue = mb_strtolower(trim((string) ($answer[$blankIdx] ?? '')));
            $accepted = $blank['accepted_answers'] ?? [];
            $found = false;

            foreach ($accepted as $valid) {
                if ($userValue === mb_strtolower(trim($valid))) {
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                return false;
            }
        }

        return true;
    }

    protected function getExpectedAnswer(string $type, array $question): mixed
    {
        return match ($type) {
            'single_choice' => $question['correct'] ?? null,
            'multiple_choice' => $question['correct'] ?? [],
            'true_false' => $question['correct'] ?? null,
            'short_answer' => $question['accepted_answers'] ?? [],
            'ordering' => $question['correct_order'] ?? [],
            'matching' => $question['correct_pairs'] ?? [],
            'cloze' => collect($question['blanks'] ?? [])->pluck('accepted_answers')->all(),
            default => null,
        };
    }
}
