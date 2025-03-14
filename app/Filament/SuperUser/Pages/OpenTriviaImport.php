<?php

namespace App\Filament\SuperUser\Pages;

use App\Actions\FetchTriviaQuestions;
use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Filament\SuperUser\Resources\QuestionResource;
use App\Models\Category;
use App\Models\Question;
use App\Models\SuperUser;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

use function Filament\authorize;

class OpenTriviaImport extends Page implements HasForms, HasTable
{
    use InteractsWithFormActions,
        InteractsWithForms,
        InteractsWithTable;

    protected static string $view = 'filament.super-user.pages.open-trivia-import';

    protected static ?string $title = 'OpenTrivia Import';

    protected static ?string $navigationLabel = 'OpenTrivia Import';

    protected static ?string $navigationGroup = 'Tools';

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = -1;

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
            'amount' => 50,
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
                        ->options(FetchTriviaQuestions::OPEN_TRIVIA_TYPES)
                        ->placeholder('Any')
                        ->rules(['nullable', 'in:'.implode(',', array_keys(FetchTriviaQuestions::OPEN_TRIVIA_TYPES))]),
                    Components\Select::make('difficulty')
                        ->label('Difficulty')
                        ->options(FetchTriviaQuestions::OPEN_TRIVIA_DIFFICULTIES)
                        ->placeholder('Any')
                        ->rules(['nullable', 'in:'.implode(',', array_keys(FetchTriviaQuestions::OPEN_TRIVIA_DIFFICULTIES))]),
                ]),
        ];
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset')
                ->action(fn () => $this->resetForm())
                ->color('gray'),
            Action::make('import')
                ->label('Import Questions')
                ->requiresConfirmation()
                ->extraAttributes(['class' => 'ml-auto'])
                ->modalIcon('heroicon-o-arrow-up-on-square')
                ->modalDescription('Click confirm to import questions from the OpenTrivia Database.')
                ->keyBindings(['mod+s'])
                ->action(fn () => $this->save()),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Question::query()->whereIn('id', $this->importedQuestions ?? []))
            ->groups([
                Group::make('question_type')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Question $record): string => $record->question_type->getLabel()),
                Group::make('difficulty')
                    ->collapsible()
                    ->getTitleFromRecordUsing(fn (Question $record): string => $record->difficulty->getLabel()),
                Group::make('category.name')->collapsible(),
            ])
            ->defaultGroup('question_type')
            ->groupingDirectionSettingHidden()
            ->columns([
                Tables\Columns\TextColumn::make('text')
                    ->searchable()
                    ->sortable()
                    ->width('5/12')
                    ->extraAttributes(['class' => 'whitespace-normal']),
                Tables\Columns\TextColumn::make('question_type')
                    ->sortable()
                    ->formatStateUsing(fn (QuestionType $state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('difficulty')
                    ->sortable()
                    ->formatStateUsing(fn (QuestionDifficulty $state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(QuestionType::getLabels()),
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(QuestionDifficulty::getLabels()),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn (Question $record): string => QuestionResource::getUrl('edit', [$record]))
                    ->visible(fn (Question $record) => authorize('update', $record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->emptyStateHeading('No Imported Questions yet')
            ->emptyStateDescription('Imported questions from the OpenTrivia Database will show here.');
    }

    // Define a method to handle the form submission
    public function save(): void
    {
        $this->authorizeAccess();

        $this->importedQuestions = [];

        [
            'fetchedQuestions' => $total,
            'fetchedQuestionIds' => $ids,
            'successful' => $successful,
            'duplicates' => $duplicates,
            'failed' => $failed,
        ] = (array) $this->importData();

        $this->getNotification(
            "Imported {$successful} ".str('question')->plural($successful)." successfully of {$total} ".str('question')->plural($total).". Duplicates: {$duplicates}, Failed: {$failed}",
            $successful > 0 ? 'success' : 'danger',
        )->send();

        if (! empty($ids)) {
            $this->importedQuestions = $ids;
        }

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

    public function resetForm(): void
    {
        $this->setDefaultData();

        $this->importedQuestions = [];
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
