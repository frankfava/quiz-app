<?php

namespace App\Filament\SuperUser\Resources;

use App\Filament\Resource;
use App\Filament\SuperUser\Resources\TenantResource\Pages;
use App\Filament\SuperUser\Resources\TenantResource\RelationManagers;
use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\GlobalSearch;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->inlineLabel()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(true)
                            ->autocomplete(false)
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $old, ?string $state) {
                                if (($get('slug') ?? '') !== Str::slug($old)) {
                                    return;
                                }
                                $set('slug', Str::slug($state));
                            }),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->rules(['alpha_dash'])
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('domain')
                            ->visible(fn () => (new Tenant)->isFillable('domain'))
                            ->label('Custom Domain')
                            ->maxLength(255),
                        Forms\Components\Toggle::make('foc')
                            ->label('Free of Charge')
                            ->inline(false),
                        Forms\Components\Placeholder::make('url')
                            ->label('Site URL')
                            ->content(fn (Tenant $record) => new HtmlString('<a href="'.$record->url.'" class="font-semibold block mb-2" style="font-size:1.2em;" target="_blank">'.$record->url.'</a>')),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('url')
                    ->getStateUsing(fn (Tenant $record) => $record->url)
                    ->url(fn ($record): string => $record->url, shouldOpenInNewTab : true)
                    ->tooltip('Visit'),
                Tables\Columns\IconColumn::make('foc')
                    ->label('FOC')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user_count')
                    ->label('Active Users')
                    ->numeric()
                    ->alignCenter()
                    ->state(fn (Tenant $record) => $record->users->count()),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('users')
                    ->multiple()
                    ->preload()
                    ->relationship(
                        name : 'users',
                        titleAttribute : 'name',
                        modifyQueryUsing : fn (Builder $query) => $query
							->addSelect(DB::raw("(users.first_name || ' ' || users.last_name) AS name"))
							->orderBy('name')
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (Tenant $record): bool => auth()->user()->can('delete', $record)),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug', 'domain'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return array_filter([
            'Slug' => $record->slug,
            'Active Users' => $record->users->count(),
            'FOC' => ($record->foc ? 'Yes' : 'No'),
        ]);
    }

    public static function getGlobalSearchResultActions(Model $record): array
    {
        return [
            GlobalSearch\Actions\Action::make('view')
                ->label($record->url)
                ->url($record->url, shouldOpenInNewTab : true),
        ];
    }
}
