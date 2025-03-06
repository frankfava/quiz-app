<?php

namespace App\Filament\User\Resources;

use App\Filament\Resource;
use App\Filament\Shared\RelationManagers as SharedRelationManagers;
use App\Filament\User\Resources\QuizResource\Pages;
use App\Filament\User\Resources\QuizResource\RelationManagers;
use App\Filament\User\Resources\QuizResource\Widgets\GenerateQuizWidget;
use App\Models\Quiz;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Quizzes';

    public static function canViewAny(): bool
    {
        return auth()->user()->tenants()->exists();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('label')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('type')
                            ->label('Quiz Type')
                            ->placeholder('e.g., standard, timed')
                            ->nullable(),
                    ]),
                Forms\Components\Livewire::make(GenerateQuizWidget::class)
                    ->hidden(fn ($operation) => $operation != 'edit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->sortable(),
                Tables\Columns\TextColumn::make('owner.name')
                    ->label('Owner')
                    ->disabled()
                    ->sortable(),
                Tables\Columns\TextColumn::make('question_count')
                    ->label('Questions')
                    ->numeric()
                    ->alignCenter()
                    ->state(function (Quiz $record) {
                        return $record->questions->count();
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
            'index' => Pages\ListQuizzes::route('/'),
            'create' => Pages\CreateQuiz::route('/create'),
            'edit' => Pages\EditQuiz::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            SharedRelationManagers\QuestionsRelationManager::class,
            RelationManagers\SubmissionsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            GenerateQuizWidget::class,
        ];
    }
}
