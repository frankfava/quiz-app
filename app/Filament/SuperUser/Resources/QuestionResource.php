<?php

namespace App\Filament\SuperUser\Resources;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Filament\SuperUser\Resources\QuestionResource\Pages;
use App\Models\Category;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionResource extends Resource
{
    protected static ?string $model = Question::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Quizzes';

    public static function canViewAny(): bool
    {
        return auth()->user()->can('viewAny', Question::class);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('text')
                    ->label('Question Text')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('question_type')
                    ->label('Type')
                    ->options(collect(QuestionType::cases())->pluck('value', 'value')->toArray())
                    ->required(),
                Forms\Components\Select::make('difficulty')
                    ->label('Difficulty')
                    ->options(collect(QuestionDifficulty::cases())->pluck('value', 'value')->toArray())
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id')->toArray())
                    ->required()
                    ->searchable(),
                Forms\Components\KeyValue::make('options')
                    ->label('Options')
                    ->nullable()
                    ->keyLabel('Option Key')
                    ->valueLabel('Option Value'),
                Forms\Components\TextInput::make('correct_answer')
                    ->label('Correct Answer')
                    ->required()
                    ->helperText('Enter as JSON, e.g., ["A"] for multiple choice or "true" for boolean.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('text')
                    ->searchable()
                    ->sortable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('question_type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('difficulty')
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),
                Tables\Columns\TextColumn::make('correct_answer')
                    ->formatStateUsing(fn ($state) => json_encode($state))
                    ->limit(20),
            ])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultPaginationPageOption(100)
            ->filters([
                Tables\Filters\SelectFilter::make('question_type')
                    ->options(collect(QuestionType::cases())->pluck('value', 'value')->toArray()),
                Tables\Filters\SelectFilter::make('difficulty')
                    ->options(collect(QuestionDifficulty::cases())->pluck('value', 'value')->toArray()),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(Category::pluck('name', 'id')->toArray()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuestions::route('/'),
            'create' => Pages\CreateQuestion::route('/create'),
            'edit' => Pages\EditQuestion::route('/{record}/edit'),
        ];
    }
}
