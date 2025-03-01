<?php

namespace App\Filament\User\Pages\Tenancy;

use App\Models\Tenant;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Tenancy\RegisterTenant as RegisterTenantBase;
use Illuminate\Support\Str;

class RegisterTenant extends RegisterTenantBase
{
    public static ?string $navigationIcon = 'heroicon-o-plus';

    protected static ?string $navigationLabel = 'Create New Site';

    public static function getLabel(): string
    {
        return self::$navigationLabel;
    }

    public function form(Form $form): Form
    {
        return $form
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
                    ->live()
                    ->unique()
                    ->helperText(function (?string $state) {
                        return $state ? 'eg. '.Tenant::getUrlWithSlug($state) : '';
                    }),
            ]);
    }

    protected function handleRegistration(array $data): Tenant
    {
        $tenant = Tenant::create([
            'name' => $data['name'],
            'slug' => $data['slug'],
        ]);

        $tenant->users()->attach(auth()->user());

        return $tenant;
    }
}
