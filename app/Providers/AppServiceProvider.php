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
}
