<?php

namespace App\Filament\Tenancy\Middleware;

use Closure;
use Exception;
use Filament\Facades\Filament;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Http\Request;

class SetDefaultFilamentTenant
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        /** @var Panel $panel */
        $panel = Filament::getCurrentPanel();

        if (! $panel->hasTenancy()) {
            return $next($request);
        }

        /** @var Model $user */
        $user = $panel->auth()->user();

        if (! $user instanceof HasTenants) {
            return $next($request);
        }

        $tenant = null;
        if ($request->route()->hasParameter('tenant')) {
            try {
                $tenant = $panel->getTenant($request->route()->parameter('tenant'));
            } catch (Exception $e) {
            }
        }
        if (! $tenant) {
            $tenant = Filament::getUserDefaultTenant(Filament::auth()->user());
        }

        if (! $tenant) {
            abort(404);
        }

        if (! $user->canAccessTenant($tenant)) {
            return $next($request);
        }

        Filament::setTenant($tenant);

        return $next($request);
    }
}
