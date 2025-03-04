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

    const CREATED_QUESTION = 'CREATED_QUESTION';

    const DUPLICATE_QUESTION = 'DUPLICATE_QUESTION';

    const INVALID_QUESTION = 'INVALID_QUESTION';

    const OPEN_TRIVIA_TYPES = [
        'multiple' => 'Multiple Choice',
        'boolean' => 'True / False',
    ];

    const OPEN_TRIVIA_CATEGORIES = [
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

    const OPEN_TRIVIA_DIFFICULTIES = [
        'easy' => 'Easy',
        'medium' => 'Medium',
        'hard' => 'Hard',
    ];

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
            'fetchedQuestionIds' => [],
            'successful' => 0,
            'duplicates' => 0,
            'failed' => 0,
        ];

        $type = match ($this->type) {
            QuestionType::MULTIPLE_CHOICE => 'multiple',
            QuestionType::BOOLEAN => 'boolean',
            default => null
        };

        $totalQuestions = max(0, min(10000, $this->totalQuestions));

        while ($result->fetchedQuestions < $totalQuestions) {

            if ($type && ! in_array($type, ['multiple', 'boolean'])) {
                throw new InvalidArgumentException('Invalid type. Use "multiple" or "boolean".');
            }

            $response = Http::get(self::API_URL, array_filter([
                'amount' => $this->questionsPerBatch,
                'difficulty' => $this->difficulty ? $this->difficulty->value : null,
                'type' => $type ? $type : null,
                'category' => $this->category ? $this->checkCategory($this->category) : null,
            ]));
            $json = $response->json();

            if ($json['response_code'] != 0) {
                continue;
            }

            $questions = $json['results'] ?? [];

            if (! empty($questions)) {
                foreach ($questions as $question) {
                    if ($result->fetchedQuestions >= $totalQuestions) {
                        break;
                    }
                    $result->fetchedQuestions++;

                    ['status' => $status,'question' => $question] = $this->storeQuestion($question);

                    match ($status) {
                        self::CREATED_QUESTION => $result->successful++,
                        self::DUPLICATE_QUESTION => $result->duplicates++,
                        default => $result->failed++,
                    };

                    if ($question instanceof Question) {
                        $result->fetchedQuestionIds[] = $question->id;
                    }
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

    protected function storeQuestion(array $questionData): array
    {
        /** @var string */
        $status = self::INVALID_QUESTION;

        /** @var Question */
        $question = null;

        $questionData['question'] = html_entity_decode(htmlspecialchars_decode($questionData['question']), ENT_QUOTES, 'UTF-8');
        $questionData['category'] = html_entity_decode(htmlspecialchars_decode($questionData['category']));
        $questionData['correct_answer'] = htmlspecialchars_decode($questionData['correct_answer']);
        $questionData['incorrect_answers'] = json_decode(htmlspecialchars_decode(json_encode($questionData['incorrect_answers'] ?? null)), 1);

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
                    QuestionType::MULTIPLE_CHOICE => collect(array_merge([$questionData['correct_answer']], $questionData['incorrect_answers'] ?? []))->shuffle()->toArray(),
                    default => null,
                };

                $mappedOptions = match ($questionType) {
                    QuestionType::MULTIPLE_CHOICE => array_combine(array_map(
                        fn ($index) => chr(65 + $index), // 65 is ASCII for 'A'
                        range(0, count($options) - 1)
                    ), $options),
                    default => null,
                };

                $status = self::CREATED_QUESTION;
                $question = Question::create([
                    'text' => $questionData['question'],
                    'question_type' => $questionType,
                    'difficulty' => $questionData['difficulty'],
                    'category_id' => $this->getCategoryId($questionData['category']),
                    'options' => $mappedOptions,
                    'correct_answer' => $questionType === QuestionType::MULTIPLE_CHOICE
                        ? [array_search($questionData['correct_answer'], $mappedOptions)]
                        : (bool) ($questionData['correct_answer'] == 'True'),
                    'content_hash' => $contentHash,
                ]);
            } else {
                $status = self::DUPLICATE_QUESTION;
                $question = $existingQuestion;
            }
        } catch (\Exception $e) {
            $status = self::INVALID_QUESTION;
        }

        return [
            'status' => $status,
            'question' => $question,
        ];
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
        $categories = self::OPEN_TRIVIA_CATEGORIES;

        if (is_numeric($categoryNameOrId)) {
            return $categories[$categoryNameOrId] ? $categoryNameOrId : null;
        }

        return ($index = array_search($categoryNameOrId, $categories)) !== false ? $index : null;
    }
}
