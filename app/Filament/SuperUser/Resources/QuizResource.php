<?php

namespace App\Filament\SuperUser\Resources;

use App\Filament\SuperUser\Resources\QuizResource\Pages;
use App\Models\Quiz;
use App\Models\Tenant;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuizResource extends Resource
{
    protected static ?string $model = Quiz::class;

    protected static ?string $navigationIcon = 'heroicon-o-puzzle-piece';

    protected static ?string $navigationGroup = 'Quizzes';

    public static function canViewAny(): bool
    {
        return ($user = auth()->user()) && $user->can('viewAny', Quiz::class);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('tenant_id')
                    ->label('Tenant')
                    ->options(Tenant::pluck('name', 'id')->toArray())
                    ->nullable()
                    ->searchable(),
                Forms\Components\Select::make('created_by_id')
                    ->label('Created By')
                    ->options(User::pluck('email', 'id')->toArray())
                    ->required()
                    ->searchable(),
                Forms\Components\KeyValue::make('meta')
                    ->label('Meta Data')
                    ->nullable()
                    ->keyLabel('Key')
                    ->valueLabel('Value'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->sortable()
                    ->default('Free'),
                Tables\Columns\TextColumn::make('owner.email')
                    ->label('Created By')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tenant_id')
                    ->label('Tenant')
                    ->options(Tenant::pluck('name', 'id')->prepend('Free', '')->toArray()),
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
}
