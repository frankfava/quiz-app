<?php

namespace App\Filament\SuperUser\Resources\UserResource\RelationManagers;

use App\Enums\UserRole;
use App\Filament\SuperUser\Resources;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

use function Filament\authorize;

class TenantsRelationManager extends RelationManager
{
    protected static string $relationship = 'tenants';

    protected static ?string $inverseRelationship = 'users';

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
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
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
                    ->label('Add Tenant to User')
                    ->visible(fn (?Tenant $record) => authorize('create', Tenant::class))
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
                    ->visible(fn (Tenant $record) => authorize('update', $record)),
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->url(fn (Model $record): string => Resources\TenantResource::getUrl('edit', [$record]))
                        ->label('Edit Tenant')
                        ->visible(fn (Tenant $record) => authorize('update', $record)),
                    Tables\Actions\DetachAction::make()
                        ->label('Remove')
                        ->recordTitle(fn (Model $record) => $record->name)
                        ->visible(fn (Tenant $record) => authorize('delete', $record)),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Remove Access'),
                ]),
            ]);
    }
}
