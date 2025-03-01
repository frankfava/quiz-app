<?php

namespace App\Filament\User\Pages\Auth;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Http\Responses\Auth\Contracts\RegistrationResponse;
use Filament\Notifications\Notification;
use Filament\Pages\Auth\Register as BaseRegister;
use Illuminate\Auth\Events\Registered;

class Register extends BaseRegister
{
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => request()->email,
        ]);
    }

    /**
     * Submit Action
     *
     * OVERRRIDE Default to Test against valid Tenant Invitation
     */
    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::pages/auth/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::pages/auth/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();

        $email = $data['email'];

        $user = $this->getUserModel()::create($data);

        event(new Registered($user));

        // TODO: If Tenant is allowing Logins at the moment

        // TODO: If Tenant wants users logged in after registration
        Filament::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    protected function getForms(): array
    {
        return [
            'form' => $this->form(
                $this->makeForm()
                    ->columns(2)
                    ->schema([
                        TextInput::make('first_name')
                            ->label(__('First Name'))
                            ->required()
                            ->maxLength(255)
                            ->autofocus(),
                        TextInput::make('last_name')
                            ->label(__('Last Name'))
                            ->required()
                            ->maxLength(255),
                        $this->getEmailFormComponent()
                            ->readonly(request()->has('email'))
                            ->columnSpanFull(),
                        $this->getPasswordFormComponent()
                            ->columnSpanFull(),
                        $this->getPasswordConfirmationFormComponent()
                            ->columnSpanFull(),
                    ])
                    ->statePath('data'),
            ),
        ];
    }
}
