<?php

namespace App\Actions;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use Closure;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class FetchTriviaQuestions
{
    const API_URL = 'https://opentdb.com/api.php?';

    protected ?Closure $afterEachTry;

    protected ?Closure $onCompletion;

    const DUPLICATE_QUESTION = 'DUPLICATE_QUESTION';

    const INVALID_QUESTION = 'INVALID_QUESTION';

    public function __construct(
        readonly protected ?int $totalQuestions = 1000,
        readonly protected ?QuestionDifficulty $difficulty = null,
        readonly protected ?QuestionType $type = null,
        readonly protected null|string|int $category = null,
        readonly protected int $questionsPerBatch = 50,
        ?Closure $afterEachTry = null,
        ?Closure $onCompletion = null,
    ) {
        $this->afterEachTry = $afterEachTry ?: null;
        $this->onCompletion = $onCompletion ?: null;
    }

    public function execute()
    {
        $result = (object) [
            'fetchedQuestions' => 0,
            'successful' => 0,
            'duplicates' => 0,
            'failed' => 0,
        ];

        while ($result->fetchedQuestions < $this->totalQuestions) {

            if ($this->type && ! in_array($this->type, ['multiple', 'boolean'])) {
                throw new InvalidArgumentException('Invalid type. Use "multiple" or "boolean".');
            }

            $response = Http::get(self::API_URL, array_filter([
                'amount' => $this->questionsPerBatch,
                'difficulty' => $this->difficulty ? $this->difficulty->value : null,
                'type' => $this->type ? $this->type->value : null,
                'category' => $this->category ? $this->checkCategory($this->category) : null,
            ]));
            $json = $response->json();

            if ($json['response_code'] != 0) {
                continue;
            }

            $questions = $json['results'] ?? [];

            if (! empty($questions)) {
                foreach ($questions as $question) {
                    if ($result->fetchedQuestions >= $this->totalQuestions) {
                        break;
                    }
                    $result->fetchedQuestions++;

                    match ($this->storeQuestion($question)) {
                        true => $result->successful++,
                        self::DUPLICATE_QUESTION => $result->duplicates++,
                        default => $result->failed++,
                    };
                }
            }

            if ($this->afterEachTry instanceof Closure) {
                call_user_func($this->afterEachTry, $result->fetchedQuestions, count($questions));
            }
        }

        if ($this->onCompletion instanceof Closure) {
            call_user_func($this->onCompletion, $result->fetchedQuestions);
        }

        return $result;
    }

    protected function storeQuestion(array $questionData): true|string
    {
        try {
            $contentHash = Question::generateContentHash($questionData);

            // Check if question already exists based on the content hash
            $existingQuestion = Question::where('content_hash', $contentHash)->exists();

            if (! $existingQuestion) {
                $questionType = match ($questionData['type']) {
                    'multiple' => QuestionType::MULTIPLE_CHOICE,
                    'boolean' => QuestionType::BOOLEAN,
                };

                $options = match ($questionType) {
                    QuestionType::MULTIPLE_CHOICE => $options = array_merge([$questionData['correct_answer']], $questionData['incorrect_answers']),
                    default => null,
                };

                $mappedOptions = match ($questionType) {
                    QuestionType::MULTIPLE_CHOICE => array_combine(['A', 'B', 'C', 'D'], $options),
                    default => null,
                };

                Question::forceCreate([
                    'text' => $questionData['question'],
                    'question_type' => $questionType,
                    'category_id' => $this->getCategoryId($questionData['category']),
                    'difficulty' => $questionData['difficulty'],
                    'correct_answer' => $questionData['correct_answer'],
                    'options' => $mappedOptions,
                    'correct_answer' => $questionType === QuestionType::MULTIPLE_CHOICE
                        ? [array_search($questionData['correct_answer'], $mappedOptions)]
                        : (bool) $questionData['correct_answer'],
                    'content_hash' => $contentHash,
                ]);

                return true;
            } else {
                return self::DUPLICATE_QUESTION;
            }
        } catch (\Exception $e) {
            return self::INVALID_QUESTION;
        }

        return self::INVALID_QUESTION;
    }

    /** Create or Get Category Model */
    protected function getCategoryId($categoryName)
    {
        // If Category doesn't exist, create it
        $category = \App\Models\Category::firstOrCreate(
            ['name' => $categoryName],
            ['slug' => str($categoryName)->slug()->toString(), 'description' => null]
        );

        return $category->id;
    }

    /** Get Open Trivia DB Category */
    protected function checkCategory(int|string $categoryNameOrId): ?int
    {
        $categories = [
            9 => 'General Knowledge',
            10 => 'Entertainment: Books',
            11 => 'Entertainment: Film',
            12 => 'Entertainment: Music',
            13 => 'Entertainment: Musicals & Theatres',
            14 => 'Entertainment: Television',
            15 => 'Entertainment: Video Games',
            16 => 'Entertainment: Board Games',
            17 => 'Science & Nature',
            18 => 'Science: Computers',
            19 => 'Science: Mathematics',
            20 => 'Mythology',
            21 => 'Sports',
            22 => 'Geography',
            23 => 'History',
            24 => 'Politics',
            25 => 'Art',
            26 => 'Celebrities',
            27 => 'Animals',
            28 => 'Vehicles',
            29 => 'Entertainment: Comics',
            30 => 'Science: Gadgets',
            31 => 'Entertainment: Japanese Anime & Manga',
            32 => 'Entertainment: Cartoon & Animations',
        ];

        if (is_numeric($categoryNameOrId)) {
            return $categories[$categoryNameOrId] ? $categoryNameOrId : null;
        }

        return ($index = array_search($categoryNameOrId, $categories)) !== false ? $index : null;
    }
}
