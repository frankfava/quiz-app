<?php

namespace App\Filament\User\Resources\QuizResource\Widgets;

use App\Filament\User\Resources\QuizResource;
use App\Models\Category;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Support\Colors\Color;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class GenerateQuizWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.user.resources.quiz-resource.widgets.generate-quiz-widget';

    protected int|string|array $columnSpan = 'full';

    public ?array $data = [];

    public array $counts = [];

    public ?Model $record = null;

    public function mount(?Model $record = null): void
    {
        $this->record = $record;

        $this->getQuestionCounts();

        $this->form->fill([
            'question_count' => 30,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Section::make('Generate Questions')
                    ->columns(['xl' => 2, 'lg' => 1])
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->options(Category::all()
                                ->mapWithKeys(fn ($category) => [$category->id => $category->name])
                                ->toArray())
                            ->searchable()
                            ->label('Category'),
                        Forms\Components\Select::make('question_type')
                            ->options(
                                collect(\App\Enums\QuestionType::cases())
                                    ->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()])
                                    ->toArray()
                            )
                            ->searchable()
                            ->label('Question Type'),
                        Forms\Components\Select::make('difficulty')
                            ->options(
                                collect(\App\Enums\QuestionDifficulty::cases())
                                    ->mapWithKeys(fn ($difficulty) => [$difficulty->value => $difficulty->getLabel()])
                                    ->toArray()
                            )
                            ->searchable()
                            ->label('Difficulty'),
                        Forms\Components\TextInput::make('question_count')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(100)
                            ->required()
                            ->label('Number of Questions'),
                        Forms\Components\Actions::make([])
                            ->columnSpanFull()
                            ->actions([
                                Forms\Components\Actions\Action::make('generate')
                                    ->label('Generate Questions')
                                    ->color(Color::Slate)
                                    ->action('generateQuestions')
                                    ->requiresConfirmation()
                                    ->modalHeading('Confirm Question Generation')
                                    ->modalDescription('Are you sure you want to generate new questions? This will replace existing ones.')
                                    ->modalSubmitActionLabel('Yes, Generate'),
                                Forms\Components\Actions\Action::make('viewCounts')
                                    ->label('View Field Counts')
                                    ->color(Color::Gray)
                                    ->outlined()
                                    ->extraAttributes(['class' => 'ml-auto'])
                                    ->action(fn () => $this->dispatch('open-modal', id: 'field-counts')),
                            ]),
                    ]),
            ]);
    }

    protected function getQuestionCounts(): void
    {
        $fields = [
            'category_id' => 'Categories',
            'question_type' => 'Question Types',
            'difficulty' => 'Difficulties',
        ];

        foreach ($fields as $field => $label) {
            $counts = Question::query()
                ->selectRaw("$field, COUNT(*) as count")
                ->groupBy($field)
                ->pluck('count', $field)
                ->toArray();

            $labels = match ($field) {
                'category_id' => Category::pluck('name', 'id')->sortKeys()->toArray(),
                'question_type' => collect(\App\Enums\QuestionType::cases())
                    ->mapWithKeys(fn ($type) => [$type->value => $type->getLabel()])
                    ->sortKeys()
                    ->toArray(),
                'difficulty' => collect(\App\Enums\QuestionDifficulty::cases())
                    ->mapWithKeys(fn ($difficulty) => [$difficulty->value => $difficulty->getLabel()])
                    ->sortKeys()
                    ->toArray(),
            };

            $this->counts[$label] = collect($counts)->mapWithKeys(
                fn ($count, $key) => [$labels[$key] ?? $key => $count]
            )->toArray();
        }
    }

    public function generateQuestions(): void
    {
        $data = $this->form->getState();
        $tenantId = Tenant::current()->id;

        $query = Question::query()
            ->when($data['category_id'], fn ($q) => $q->where('category_id', $data['category_id']))
            ->when($data['question_type'], fn ($q) => $q->where('question_type', $data['question_type']))
            ->when($data['difficulty'], fn ($q) => $q->where('difficulty', $data['difficulty']));

        $questions = $query->inRandomOrder()
            ->limit($data['question_count'])
            ->get();

        if ($questions->count() < $data['question_count']) {
            Notification::make()
                ->warning()
                ->title('Not enough questions match your criteria.')
                ->send();

            return;
        }

        $quiz = $this->record ?? Quiz::create([
            'title' => 'Generated Quiz', // Default title, updated via form on save
            'tenant_id' => $tenantId,
        ]);

        $quiz->questions()->sync(
            $questions->pluck('id')->mapWithKeys(fn ($id, $index) => [$id => ['order' => $index + 1]])->toArray()
        );

        if ($this->record) {
            Notification::make()
                ->success()
                ->title('Questions generated successfully.')
                ->send();
        }

        $this->redirect(
            QuizResource::getUrl('edit', ['record' => $quiz]),
            navigate: (bool) $this->record
        );
    }
}
