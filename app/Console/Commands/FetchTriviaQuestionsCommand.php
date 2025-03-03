<?php

namespace App\Console\Commands;

use App\Actions\FetchTriviaQuestions;
use Exception;
use Illuminate\Console\Command;

use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\note;
use function Laravel\Prompts\warning;

class FetchTriviaQuestionsCommand extends Command
{
    protected $signature = 'fetch:trivia-questions 
                            {--total=1000 : Number of questions to fetch}
                            {--difficulty= : The difficulty level of the questions (easy, medium, hard)}
                            {--type= : The type of question (multiple, boolean)} 
                            {--category= : The Category of the questions} 
                            {--batch=50 : The number of questions to fetch per API request (max 50)}';

    protected $description = 'Fetch trivia questions from Open Trivia Database and store them in the database';

    public function handle()
    {
        $totalQuestions = $this->option('total');
        $difficulty = $this->option('difficulty');
        $type = $this->option('type');
        $category = $this->option('category');
        $questionsPerBatch = $this->option('batch');

        // Validate that questionsPerBatch is not greater than 50
        if ($questionsPerBatch > 50) {
            throw new Exception('The number of questions per batch cannot exceed 50.');
        }

        info("Fetching $totalQuestions trivia questions...");

        $fetchTriviaQuestionsAction = new FetchTriviaQuestions(
            totalQuestions: $totalQuestions,
            difficulty: $difficulty,
            type: $type,
            category: $category,
            questionsPerBatch: $questionsPerBatch,
            afterEachTry: function ($aggregateCount, $fetchedCount) {
                note("Fetched up to $fetchedCount ".str('question')->plural($fetchedCount).". Total: $aggregateCount");
            },
            onCompletion: fn ($fetchedCount) => info("Fetched $fetchedCount questions.")
        );

        $fetchedCount = $fetchTriviaQuestionsAction->execute();

        info('Successfully fetched '.$fetchedCount->successful.' trivia '.str('question')->plural($fetchedCount->successful).'.');

        if ($fetchedCount->duplicates) {
            warning('Skipped '.$fetchedCount->duplicates.' duplicate trivia '.str('question')->plural($fetchedCount->duplicates).'.');
        }
        if ($fetchedCount->failed) {
            error('Could not add '.$fetchedCount->failed.' trivia '.str('question')->plural($fetchedCount->failed).'.');
        }
    }
}
