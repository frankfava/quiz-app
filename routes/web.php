<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Main Domain
Route::group(['domain' => tenancy()->mainDomain()], function () {
    // Fallback
    Route::get('/', function () {
        // abort(404);
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'laravelVersion' => Application::VERSION,
            'phpVersion' => PHP_VERSION,
        ]);
    })->name('base');
    Route::fallback(fn () => redirect()->route('base'));
});

Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))->name('login');

// Tenant
Route::name('tenant.')
    ->middleware([\App\Tenancy\Middleware\CustomDomains::class])
    ->domain('{domain}')
    ->where(['domain' => '.*'])
    ->group(function () {
        Route::get('/', fn (Request $request) => Blade::render('<pre>{{ print_r($tenant->toArray(),1) }}</pre>', ['tenant' => $request->tenant->load('users')]))->name('app');

        Route::get('/{any?}', fn (Request $request) => $request->fullUrl())
            ->where(['any' => '.*'])
            ->name('any');
    });
