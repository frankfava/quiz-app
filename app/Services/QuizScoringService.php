<?php

namespace App\Services;

use App\Models\Question;
use App\Models\QuizSubmission;

class QuizScoringService
{
    public function calculateScore(QuizSubmission $submission): int
    {
        $quiz = $submission->quiz;
        $answers = $submission->answers;
        $score = 0;

        foreach ($quiz->questions as $question) {
            $userAnswer = $answers[$question->id] ?? null;
            if ($this->isCorrect($question, $userAnswer)) {
                $score++;
            }
        }

        return $score;
    }

    protected function isCorrect(Question $question, $userAnswer): bool
    {
        if (is_null($userAnswer)) {
            return false;
        }

        $correctAnswer = $question->correct_answer;

        return match ($question->question_type) {
            \App\Enums\QuestionType::MULTIPLE_CHOICE => $userAnswer === $correctAnswer[0], // Single key (e.g., "A")
            \App\Enums\QuestionType::BOOLEAN => (bool) $userAnswer === $correctAnswer,
            \App\Enums\QuestionType::OPEN_TEXT => strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer)),
            default => false, // Unsupported types for now
        };
    }
}
