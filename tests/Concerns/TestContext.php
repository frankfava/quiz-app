<?php

namespace Tests\Concerns;

use App\Models\Tenant;
use App\Models\User;
use Closure;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TestContext
{
    public $user = null;

    public $tenant = null;

    public bool $makeTenantCurrent = true;

    public ?string $role = null;

    public ?string $guard = null;

    public bool $authenticateWithToken = false;

    public array $sanctumAbilities = ['*'];

    public function __construct(private readonly TestCase $test)
    {
        $this->tenant()
            ->makeTenantCurrent(true)
            ->user();
    }

    public function user(User|Closure|null $closure = null): static
    {
        if ($closure instanceof User) {
            $this->user = $closure;
        } else {
            $this->user = User::factory();
            if (is_callable($closure)) {
                $this->user = call_user_func($closure, $this->user);
            }
        }

        return $this;
    }

    public function withoutUser(): static
    {
        $this->user = null;

        return $this;
    }

    public function authenticate($guard = 'web'): static
    {
        $this->guard = $guard;
        $this->authenticateWithToken = false;

        return $this;
    }

    public function authenticateWithToken(array $abilities = ['*'], $guard = 'api'): static
    {
        $this->authenticateWithToken = true;
        $this->sanctumAbilities = $abilities;
        $this->guard = $guard;

        return $this;
    }

    public function tenant(Tenant|Closure|null $closure = null): static
    {
        $this->role();
        if ($closure instanceof Tenant) {
            $this->tenant = $closure;
        } else {
            $this->tenant = Tenant::factory();
            if (is_callable($closure)) {
                $this->tenant = call_user_func($closure, $this->tenant);
            }
        }

        return $this;
    }

    public function makeTenantCurrent(bool $condition = true): static
    {
        $this->makeTenantCurrent = $condition;

        return $this;
    }

    public function withoutTenant(): static
    {
        $this->tenant = false;

        return $this;
    }

    public function role(?string $role = 'admin'): static
    {
        $this->role = $role;

        return $this;
    }

    public function create(bool $asArray = false): static|array
    {
        $hasTenant = (! is_null($this->tenant) && $this->tenant !== false);
        $hasUser = (! is_null($this->user) && $this->user !== false);

        if ($hasTenant) {
            $this->tenant = $this->tenant instanceof Tenant ? $this->tenant : $this->tenant->create();
            if ($this->makeTenantCurrent) {
                $this->tenant->makeThisCurrent();
            }
        }

        if ($hasUser) {
            $this->user = $this->user instanceof User ? $this->user : $this->user->create();
            if (! is_null($this->guard)) {
                if (! $this->authenticateWithToken) {
                    $this->test->actingAs($this->user, $this->guard ?? 'web');
                } else {
                    Sanctum::actingAs($this->user, $this->sanctumAbilities, $this->guard ?? 'api');
                }
            }
            if ($hasTenant && ! is_null($this->role)) {
                $this->tenant->addUser($this->user, $this->role);
            }
            $this->user = $this->user->load('tenants');
        }

        return $asArray ? get_object_vars($this) : $this;
    }
}
