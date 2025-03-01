<?php

namespace App\Providers;

use App\Rules\Slug;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications;
use Illuminate\Database\Eloquent;
use Illuminate\Encryption\Encrypter;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Illuminate\Validation\Rule;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Facades\Vite::prefetch(concurrency: 3);

        $this->changeDatabaseEncryptionKey();
        $this->setupValidationRuleMacros();
        $this->addStrMacros();
        $this->updateUrlsForFilament();
    }

    /**
     * Change the database encryption key.
     *
     * @see https://larasec.substack.com/p/security-tip-custom-encryption-key
     */
    protected function changeDatabaseEncryptionKey(): void
    {
        $key = config('database.encryption_key');
        if (! $key) {
            return;
        }
        $key = base64_decode(Str::after($key, 'base64:'));
        Eloquent\Model::encryptUsing(new Encrypter($key, config('app.cipher')));
    }

    /** Setup Validation rule macros */
    protected function setupValidationRuleMacros(): void
    {
        $macros = [
            Slug::$validationStr => Slug::class,
        ];

        collect($macros)
            ->each(function ($class, $macro) {
                if (method_exists($class, 'convertToValidationString')) {
                    Rule::macro($macro, [app($class), 'convertToValidationString']);
                }
                if (method_exists($class, 'validate')) {
                    Facades\Validator::extend($macro, [app($class), 'validate']);
                }
            });
    }

    /** Add Macros to the Str class. */
    protected function addStrMacros(): void
    {
        Str::macro('possessive', function ($subject) {
            return "$subject'".(Str::endsWith($subject, ['s', 'S']) ? '' : 's');
        });

        Stringable::macro('possessive', function () {
            return new Stringable(Str::possessive($this->value));
        });
    }

    /** Update URLs for Filament Notifications */
    protected function updateUrlsForFilament(): void
    {
        // Override Reset Password URL so it can use the filament url
        Notifications\ResetPassword::createUrlUsing(function ($notifiable, $token) {
            return Filament::getResetPasswordUrl($token, $notifiable);
        });

        // Override Verify Email so it can use the filament verification url
        Notifications\VerifyEmail::createUrlUsing(function ($notifiable) {
            return Filament::getVerifyEmailUrl($notifiable);
        });
    }
}
