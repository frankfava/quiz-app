<?php

namespace App\Filament\User\Resources\QuizResource\RelationManagers;

use App\Services\QuizScoringService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubmissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'submissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('User')
                    ->options(\App\Models\User::pluck('name', 'id')->toArray())
                    ->required(),
                Forms\Components\Textarea::make('answers')
                    ->label('Answers (JSON)')
                    ->json()
                    ->required(),
                Forms\Components\TextInput::make('score')
                    ->numeric()
                    ->readOnly(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->sortable(),
                Tables\Columns\TextColumn::make('score')
                    ->label('Score')
                    ->sortable()
                    ->getStateUsing(function ($record) {
                        $service = new QuizScoringService;

                        return $record->score ?? $service->calculateScore($record);
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record) {
                        $service = new QuizScoringService;
                        $record->update(['score' => $service->calculateScore($record)]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
}
