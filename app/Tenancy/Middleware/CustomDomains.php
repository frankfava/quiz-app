<?php

namespace App\Tenancy\Middleware;

use App\Models\Tenant;
use App\Tenancy\Tenancy;
use Closure;
use Illuminate\Http\Request;

class CustomDomains
{
    public function __construct(
        private readonly Tenancy $tenancy
    ) {}

    /**
     * Handle an incoming request.
     *
     * @see https://github.com/archtechx/tenancy/blob/3.x/src/Middleware/InitializeTenancyByDomainOrSubdomain.php
     * @see https://joelennon.com/dynamic-custom-domain-routing-in-laravel
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        $domain = $request->getHost();
        $tenant = null;

        if ($domain == $this->tenancy->mainDomain()) {
            return $next($request);
        }

        if ($subdomain = $this->makeSubdomain($domain)) {
            $tenant = Tenant::whereSlug($subdomain)->first();
        } else {
            $tenant = Tenant::whereDomain($domain)->first();
        }

        if (! $tenant) {
            abort(404);
        }

        $this->tenancy->makeTenantCurrent($tenant);

        $request->merge([
            'domain' => $domain,
            'isSubdomain' => (bool) $subdomain,
            'tenant' => $tenant,
        ]);

        // View::share('tenantColor', $tenant->color);
        // View::share('tenantName', $tenant->name);

        return $next($request);
    }

    protected function makeSubdomain(string $hostname)
    {
        $check = $this->tenancy->checkDomain($hostname);

        if (! $check['subdomain'] || ! str($hostname)->endsWith($this->tenancy->mainDomain())) {
            return false;
        }

        return $check['subdomain'];
    }
}
