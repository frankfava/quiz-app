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

// Tenant
Route::name('tenant.')
    ->middleware([\App\Tenancy\Middleware\CustomDomains::class])
    ->domain('{domain}')
    ->where(['domain' => '.*'])
    ->group(function () {
        Route::get('/{any?}', function (Request $request) {
            return Blade::render('<pre>{{ print_r($tenant->toArray(),1) }}</pre>', ['tenant' => $request->tenant->load('users')]);
        })
            ->where(['any' => '.*'])
            ->name('app');
    });
