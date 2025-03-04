<?php

namespace App\Filament\SuperUser\Resources\QuizResource\RelationManagers;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class QuestionsRelationManager extends RelationManager
{
    protected static string $relationship = 'questions';

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\Select::make('id')
                    ->label('Question')
                    ->options(\App\Models\Question::pluck('text', 'id')->toArray())
                    ->searchable()
                    ->required(),
                Forms\Components\TextInput::make('order')
                    ->label('Order')
                    ->numeric()
                    ->default(0)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->description('Questions that belong to this quiz.')
            ->recordTitleAttribute('text')
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
                Tables\Columns\TextColumn::make('pivot.order')
                    ->label('Order'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make(),
            ])
            ->reorderable('order')
            ->reorderRecordsTriggerAction(
                fn (Tables\Actions\Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Save Order' : 'Reorder'),
            );
    }
}
