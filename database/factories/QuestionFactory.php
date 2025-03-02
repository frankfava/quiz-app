<?php

namespace Database\Factories;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Category;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(QuestionType::cases());
        $options = $this->generateOptions($type);
        $correct = $this->generateCorrectAnswer($type, $options);

        return [
            'text' => $this->faker->sentence,
            'question_type' => $type,
            'difficulty' => $this->faker->randomElement(QuestionDifficulty::cases())->value,
            'category_id' => null,
            'options' => $options ? $options : null,
            'correct_answer' => $correct,
            // 'content_hash' omitted; handled by Question::boot()
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return Factory
     */
    public function configure()
    {
        return $this->afterMaking(function (Question $question) {
            if (is_null($question->category_id)) {
                $question->category_id = (Category::count() ? Category::all()->random() : Category::factory()->create())->id;
            }
        });
    }

    private function generateOptions(QuestionType $type): ?array
    {
        return match ($type) {
            QuestionType::MULTIPLE_CHOICE,
            QuestionType::MULTIPLE_RESPONSE,
            QuestionType::ORDERING => [
                'A' => $this->faker->word,
                'B' => $this->faker->word,
                'C' => $this->faker->word,
                'D' => $this->faker->word,
            ],
            QuestionType::MATCHING => [
                'left' => ['A' => 'France', 'B' => 'Japan'],
                'right' => ['1' => 'Paris', '2' => 'Tokyo'],
            ],
            default => null,
        };
    }

    private function generateCorrectAnswer(QuestionType $type, ?array $options): array|bool|string
    {
        return match ($type) {
            QuestionType::MULTIPLE_CHOICE => [$this->faker->randomElement(array_keys($options))],
            QuestionType::OPEN_TEXT => $this->faker->word,
            QuestionType::BOOLEAN => $this->faker->boolean,
            QuestionType::MULTIPLE_RESPONSE => $this->faker->randomElements(array_keys($options), 2),
            QuestionType::ORDERING => ['B', 'A', 'C', 'D'], // Example order
            QuestionType::MATCHING => ['A' => '1', 'B' => '2'],
            default => [],
        };
    }
}
