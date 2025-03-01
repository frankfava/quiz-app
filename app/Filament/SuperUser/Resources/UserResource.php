<?php

namespace App\Filament\SuperUser\Resources;

use App\Filament\Resource;
use App\Filament\SuperUser\Resources\UserResource\Pages;
use App\Filament\SuperUser\Resources\UserResource\RelationManagers;
use App\Filament\SuperUser\Tables\Actions\ImpersonateUser;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'Users';

    protected static ?string $navigationLabel = 'App Users';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->inlineLabel()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label(__('filament-panels::pages/auth/edit-profile.form.password.label'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->rule(Password::default())
                            ->autocomplete('new-password')
                            ->dehydrated(fn ($state): bool => filled($state))
                            ->dehydrateStateUsing(fn ($state): string => Hash::make($state))
                            ->live(debounce: 500)
                            ->same('passwordConfirmation'),
                        Forms\Components\TextInput::make('passwordConfirmation')
                            ->label(__('filament-panels::pages/auth/edit-profile.form.password_confirmation.label'))
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->visible(fn (Forms\Get $get): bool => filled($get('password')))
                            ->dehydrated(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->sortable(['first_name', 'last_name'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->copyable()
                    ->searchable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Email Verified')
                    ->alignCenter()
                    ->boolean()
                    ->state(function (Model $record) {
                        return ! is_null($record->email_verified_at);
                    }),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('tenant_count')
                    ->label('Active Tenants')
                    ->numeric()
                    ->alignCenter()
                    ->state(function (Model $record) {
                        return $record->tenants->count();
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\SelectFilter::make('tenants')
                    ->multiple()
                    ->preload()
                    ->relationship('tenants', 'name'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tables\Actions\DeleteAction::make(),
                ImpersonateUser::make()
                    ->visible(fn (User $record): bool => auth()->user()->can('update', $record)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (User $record): bool => auth()->user()->can('delete', $record)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TenantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'email'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Email' => $record->email,
            'Verified' => ((bool) $record->email_verified_at ? 'Yes' : 'No'),
            'Active Tenants' => $record->tenants->count(),
        ];
    }
}
