<?php

namespace App\Filament\User\Pages\Auth;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $title = 'User Profile';

    protected static ?string $navigationLabel = 'User Profile';

    protected ?string $subheading = 'Update your User Account';

    public static function isSimple(): bool
    {
        return false;
    }

    public static function getLabel(): string
    {
        return self::$title;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        $this->getNameFormComponent(),
                        $this->getEmailFormComponent(),
                        $this->getPasswordFormComponent(),
                        $this->getPasswordConfirmationFormComponent(),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->extraAttributes(['class' => 'ml-auto'])
                ->color('success'),
            $this->getCancelFormAction(),
        ];
    }

    public function getMaxContentWidth(): ?string
    {
        return 'screen-lg';
    }
}
