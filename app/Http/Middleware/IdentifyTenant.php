<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class IdentifyTenant
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Extract slug from URL path (e.g., https://quiz-app.test/tenant/futuresmiths)
        $slug = $request->segment(2); // Assumes /tenant/{slug} routing
        $tenant = Tenant::where('slug', $slug)->first() ?? Tenant::first();

        if ($tenant) {
            $request->attributes->add(['tenant' => $tenant]);
        }

        return $next($request);
    }
}