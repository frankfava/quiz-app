<?php

namespace App\Filament\SuperUser\Pages;

use App\Actions\FetchTriviaQuestions;
use App\Models\SuperUser;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Illuminate\Validation\ValidationException;

class OpenTriviaImport extends Page implements HasForms
{
    use InteractsWithFormActions,
        InteractsWithForms;

    protected static string $view = 'filament.super-user.pages.open-trivia-import';

    protected static ?string $title = 'OpenTrivia Import';

    protected static ?string $navigationLabel = 'OpenTrivia Import';

    protected static ?string $navigationGroup = 'Quizzes';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected ?string $subheading = 'Populate the Question Bank with trivia questions from the OpenTrivia Database.';

    public ?array $data = [];

    public array $importedQuestions = [];

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user() instanceof SuperUser;
    }

    public function mount(): void
    {
        $this->authorizeAccess();

        $this->fillForm();
    }

    protected function authorizeAccess(): void
    {
        abort_unless(self::shouldRegisterNavigation(), 403);
    }

    protected function defaultData(): array
    {
        return [
            'amount' => 100,
            'category' => null,
            'type' => null,
            'difficulty' => null,
        ];
    }

    protected function fillForm(array $data = []): void
    {
        $this->form->fill(array_merge($this->defaultData(), $data));
    }

    protected function getFormSchema(): array
    {
        return [
            Components\Section::make()
                ->inlineLabel()
                ->schema([
                    Components\TextInput::make('amount')
                        ->label('Number of Questions')
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->maxValue(500)
                        ->required()
                        ->rules(['integer', 'min:1', 'max:500']),
                    Components\Select::make('category')
                        ->label('Category')
                        ->options(FetchTriviaQuestions::OPEN_TRIVIA_CATEGORIES)
                        ->placeholder('Any')
                        ->rules(['nullable', 'in:'.implode(',', array_keys(FetchTriviaQuestions::OPEN_TRIVIA_CATEGORIES))]),
                    Components\Select::make('type')
                        ->label('Question Type')
                        ->options([
                            'multiple' => 'Multiple Choice',
                            'boolean' => 'True/False',
                        ])
                        ->placeholder('Any')
                        ->rules(['nullable', 'in:multiple,boolean']),
                    Components\Select::make('difficulty')
                        ->label('Difficulty')
                        ->options([
                            'easy' => 'Easy',
                            'medium' => 'Medium',
                            'hard' => 'Hard',
                        ])
                        ->placeholder('Any')
                        ->rules(['nullable', 'in:easy,medium,hard']),
                ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset')
                ->action(fn () => $this->setDefaultData())
                ->color('gray'),
            Action::make('import')
                ->label('Import Questions')
                ->requiresConfirmation()
                ->extraAttributes(['class' => 'ml-auto'])
                ->modalIcon('heroicon-o-arrow-up-on-square')
                ->modalDescription('Are you sure you\'d like to reset the data? You will lose all unsaved changes.')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
        ];
    }

    // Define a method to handle the form submission
    public function save(): void
    {
        $this->authorizeAccess();

        $result = $this->importData();

        $this->getNotification(
            "Imported {$result->successful} questions. Duplicates: {$result->duplicates}, Failed: {$result->failed}",
            $result->successful > 0 ? 'success' : 'danger',
        )->send();

        // Reset
        $this->setDefaultData();
    }

    protected function importData()
    {
        // Temporarily increase execution time to 300 seconds (5 minutes) for this request
        set_time_limit(300);

        $data = (object) $this->form->getState();

        $fetcher = new FetchTriviaQuestions(
            totalQuestions: (int) $data->amount,
            type: $data->type ? ($data->type === 'multiple' ? \App\Enums\QuestionType::MULTIPLE_CHOICE : \App\Enums\QuestionType::BOOLEAN) : null,
            category: $data->category ?: null,
        );

        return $fetcher->execute();
    }

    public function setDefaultData(): void
    {
        $this->form->fill($this->defaultData());
    }

    protected function getNotification(?string $body, ?string $status = null): Notification
    {
        return Notification::make()
            ->title($status == 'danger' ? 'Error' : ucfirst($status))
            ->body($body)
            ->status($status);
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->title($exception->getMessage())
            ->danger()
            ->send();
    }

    // Tell Filament to use the public $data property
    public function getFormStatePath(): ?string
    {
        return 'data';
    }
}
