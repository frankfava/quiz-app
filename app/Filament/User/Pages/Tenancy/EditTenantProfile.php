<?php

namespace App\Filament\User\Pages\Tenancy;

use App\Models\Tenant;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Pages\Tenancy\EditTenantProfile as EditTenantProfileBase;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class EditTenantProfile extends EditTenantProfileBase
{
    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Account Settings';

    protected ?string $subheading = 'Global options to control how your site works.';

    /** Show Theme selection on this page */
    const ALLOW_THEME_UPDATE = true;

    public static function getLabel(): string
    {
        return self::$navigationLabel;
    }

    public static function getRouteName(?string $panel = null): string
    {
        $panel ??= Filament::getCurrentPanel()->getId();

        return (string) str(static::getSlug())->replace('/', '.')->prepend("filament.{$panel}.tenant.");
    }

    protected function getDataToFillForm(array $settings = []): array
    {
        $data = [
            'tenant' => $this->tenant->attributesToArray(),
        ];

        $data = $this->mutateFormDataBeforeFill($data);

        return $data;
    }

    protected function fillForm(array $settings = []): void
    {
        $this->form->fill($this->getDataToFillForm($settings));
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                $this->tenatModelSettings(),
            ]);
    }

    /** Tenat Model Attributes */
    protected function tenatModelSettings(): Forms\Components\Section
    {
        return Forms\Components\Section::make('Account')
            ->description('Update the account settings')
            ->aside()
            ->schema([
                Forms\Components\TextInput::make('tenant.name')
                    ->required(),
                Forms\Components\TextInput::make('tenant.slug')
                    ->label('Unique Name')
                    ->maxLength(255)
                    ->required()
                    ->live(onBlur: true)
                    ->prefix('https://', true)
                    ->suffix('.'.tenancy()->mainDomain(), true)
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) {
                        $set('slug', Str::slug($state));
                    }),
                Forms\Components\TextInput::make('tenant.domain')
                    ->visible(fn () => (new Tenant)->isFillable('domain'))
                    ->label('Custom Domain (Coming Soon)')
                    ->helperText('Enter a custom domain to access your site.')
                    ->placeholder('Access your site with your own domain')
                    ->url()
                    ->disabled()
                    ->hintAction(
                        Forms\Components\Actions\Action::make('instructions')
                            ->label('Connecting your custom domain')
                            ->icon('heroicon-m-globe-alt')
                            ->disabled()
                            ->url('#')
                    )
                    ->maxLength(255),
                Forms\Components\Placeholder::make('url')
                    ->label('Your Site URL')
                    ->content(new HtmlString('<a href="'.Filament::getTenant()->url.'" class="font-semibold block mb-2" style="font-size:1.2em;">'.Filament::getTenant()->url.'</a>')),
            ]);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $data['tenant'];
        $data['slug'] = Str::slug($data['slug']);

        return $data;
    }

    protected function getRedirectUrl(): ?string
    {
        $data = $this->form->getState();

        return route('filament.admin.tenant.profile', ['tenant' => $data['tenant']['slug']]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('reset')
                ->label('Reset')
                ->extraAttributes(['class' => 'ml-auto'])
                ->requiresConfirmation()
                ->modalIcon('heroicon-o-arrow-path')
                ->modalHeading('Reset Data')
                ->modalDescription('Are you sure you\'d like to reset the data? You will lose all unsaved changes.')
                ->modalSubmitAction(function ($action) {
                    $action
                        ->label('Yes, reset it')
                        ->color('danger');
                })
                ->action(fn () => $this->resetData())
                ->color('gray'),
            Action::make('save')
                ->label(__('filament-panels::pages/tenancy/edit-tenant-profile.form.actions.save.label'))
                ->submit('save')
                ->color('success')
                ->keyBindings(['mod+s']),
        ];
    }

    /** Cancel new data input */
    public function resetData(): void
    {
        $this->fillForm();
        Notification::make()
            ->success()
            ->title('Reset')
            ->send();
    }
}
