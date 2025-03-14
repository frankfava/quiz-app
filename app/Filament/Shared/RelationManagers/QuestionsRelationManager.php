<?php

namespace App\Filament\Shared\RelationManagers;

use App\Enums\QuestionDifficulty;
use App\Enums\QuestionType;
use App\Models\Question;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

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
            ->description('Questions that belong to this '.str(class_basename($this->getOwnerRecord()))->headline()->lcfirst()->toString().'.')
            ->recordTitleAttribute('text')
            ->columns([
                Tables\Columns\TextColumn::make('text')
                    ->description(fn (Question $record) => new HtmlString($record->getOptionsAndAnswerHtml()))
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
                Tables\Columns\TextColumn::make('pivot.order')
                    ->label('Order'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->label('Add Questions')
                    ->multiple()
                    ->preloadRecordSelect(),
                // @todo: Clear all questions
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make()
                    ->label('Remove')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Question')
                    ->modalDescription('Are you sure you want to remove this question from the '.str(class_basename($this->getOwnerRecord()))->headline()->lcfirst()->toString().'?'),
            ])
            ->bulkActions([
                Tables\Actions\DetachBulkAction::make()
                    ->label('Remove Questions')
                    ->requiresConfirmation()
                    ->modalHeading('Remove Questions')
                    ->modalDescription('Are you sure you want to remove the selected questions from the '.str(class_basename($this->getOwnerRecord()))->headline()->lcfirst()->toString().'?'),
            ])
            ->reorderable('order')
            ->reorderRecordsTriggerAction(
                fn (Tables\Actions\Action $action, bool $isReordering) => $action
                    ->button()
                    ->label($isReordering ? 'Save Order' : 'Reorder'),
            )
            ->emptyStateHeading('No Quiz Questions yet')
            ->emptyStateDescription('Questions that are connected to this '.str(class_basename($this->getOwnerRecord()))->headline()->lcfirst()->toString().' will show here.');
    }
}
