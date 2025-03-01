<?php

namespace App\Filament\SuperUser\Resources\TenantResource\RelationManagers;

use App\Enums\UserRole;
use App\Filament\SuperUser\Resources;
use App\Filament\SuperUser\Tables\Actions\ImpersonateUser;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

use function Filament\authorize;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    protected static ?string $inverseRelationship = 'tenants';

    public function form(Form $form): Form
    {
        return $form
            ->columns(1)
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('role')
                    ->required()
                    ->options(UserRole::getLabels())
                    ->default(UserRole::ADMIN->value),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('email')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->state(function (Model $record) {
                        return $record->name;
                    }),
                Tables\Columns\TextColumn::make('role')
                    ->state(function (Model $record) {
                        return ucfirst($record->role->getLabel());
                    }),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->label('Add User to Tenant')
                    ->recordTitle(fn (Model $record) => $record->name)
                    ->visible(fn (?User $record) => authorize('create', User::class))
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\Select::make('role')
                            ->required()
                            ->options(UserRole::getLabels())
                            ->default(UserRole::ADMIN->value),
                    ]),

            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (User $record) => authorize('update', $record)),
                ImpersonateUser::make()
                    ->visible(fn (User $record) => authorize('update', $record)),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->url(fn (Model $record): string => Resources\UserResource::getUrl('edit', [$record]))
                        ->label('Edit User')
                        ->visible(fn (User $record) => authorize('update', $record)),
                    Tables\Actions\DetachAction::make()
                        ->label('Remove')
                        ->recordTitle(fn (Model $record) => $record->name)
                        ->visible(fn (User $record) => authorize('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove User'),
                ]),
            ]);
    }
}
