<?php

namespace App\Models;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'question_type',
        'difficulty',
        'category_id',
        'options',
        'correct_answer',
    ];

    protected $casts = [
        'text' => 'string',
        'question_type' => QuestionType::class,
        'difficulty' => QuestionDifficulty::class,
        'category_id' => 'integer',
        'options' => 'array',
        'correct_answer' => 'array',
        'content_hash' => 'string',
    ];

    protected $hidden = [
        'content_hash',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $question) {
            if (empty($question->content_hash)) {
                $contentHash = self::generateContentHash($question->toArray());
                $question->content_hash = $contentHash;
            }
            if (empty($question->difficulty)) {
                $question->difficulty = QuestionDifficulty::MEDIUM->value;
            }
        });
    }

    public static function generateContentHash(array $questionData): string
    {
        return substr(md5(json_encode($questionData)), 0, 12);
    }

    /** Scope to get questions by type */
    public function scopeByType($query, QuestionType $type)
    {
        return $query->where('type', $type->value);
    }

    /** Scope to get questions by difficulty */
    public function scopeByDifficulty($query, QuestionDifficulty $difficulty)
    {
        return $query->where('difficulty', $difficulty->value);
    }

    /** Scope to get questions for a specific category */
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function isCorrect($userAnswer): bool
    {
        $correct = $this->correct_answer;

        switch ($this->question_type) {
            case QuestionType::MULTIPLE_CHOICE:
                return $userAnswer === $correct[0];
            case QuestionType::OPEN_TEXT:
                return strtolower($userAnswer) === strtolower($correct[0]);
            case QuestionType::BOOLEAN:
                $correct = array_map('boolval', is_array($correct) ? $correct : [$correct]);

                return (bool) $userAnswer === (bool) $correct[0];
            case QuestionType::MULTIPLE_RESPONSE:
                return json_encode($userAnswer, JSON_NUMERIC_CHECK) === json_encode($correct, JSON_NUMERIC_CHECK);
            case QuestionType::ORDERING:
                return json_encode($userAnswer) === json_encode($correct);
            case QuestionType::MATCHING:
                return json_encode($userAnswer) === json_encode($correct);
            default:
                return false;
        }
    }

    public function requiresOptions(): bool
    {
        return in_array($this->question_type, [
            QuestionType::MULTIPLE_CHOICE,
            QuestionType::MULTIPLE_RESPONSE,
            QuestionType::ORDERING,
            QuestionType::MATCHING,
        ]);
    }

    public function getOptionsAndAnswerHtml(): string
    {
        $options = $this->options ?? [];
        $correctAnswer = $this->correct_answer;

        switch ($this->question_type) {
            case QuestionType::MULTIPLE_CHOICE:
            case QuestionType::MULTIPLE_RESPONSE:
                $formatted = [];
                foreach ($options as $key => $value) {
                    $isCorrect = $this->isCorrect($key);
                    $style = $isCorrect ? 'color: green;' : '';
                    $formatted[] = "<span style='{$style}'>{$key}: {$value}</span>";
                }

                return implode(' | ', $formatted);

            case QuestionType::BOOLEAN:
                $correctBool = is_array($correctAnswer) ? boolval($correctAnswer[0]) : boolval($correctAnswer);
                $trueStyle = $correctBool ? 'color: green;' : '';
                $falseStyle = ! $correctBool ? 'color: green;' : '';

                return "<span style='{$trueStyle}'>True</span> | <span style='{$falseStyle}'>False</span>";

            case QuestionType::OPEN_TEXT:
            case QuestionType::ORDERING:
            case QuestionType::MATCHING:
                $answer = is_array($correctAnswer) ? implode(', ', $correctAnswer) : $correctAnswer;

                return "<span style='color: green;'>{$answer}</span>";

            default:
                return 'N/A';
        }
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function quizzes()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_questions')
            ->using(QuizQuestion::class)
            ->withPivot('order');
    }
}
