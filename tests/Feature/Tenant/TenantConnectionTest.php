<?php

namespace Tests\Feature\Tenant;

use App\Models\Tenant;
use App\Tenancy\Events;
use App\Tenancy\Tenancy;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantConnectionTest extends TestCase
{
    /** @var Tenancy */
    protected $tenancy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenancy = app(Tenancy::class);
    }

    #[Test]
    public function can_get_container_key_for_current_tenant()
    {
        $this->assertIsString($this->tenancy->currentTenantContainerKey());
    }

    #[Test]
    public function can_get_tenant_model()
    {
        $modelClass = $this->tenancy->getTenantModelClass();
        $this->assertEquals(Tenant::class, $modelClass);
        $this->assertInstanceOf(Tenant::class, $this->tenancy->getTenantModel());
    }

    #[Test]
    public function can_bind_tenant_to_service_container()
    {
        $tenant = Tenant::factory()->create();
        $this->tenancy->bindAsCurrentTenant($tenant);

        $this->assertInstanceOf(Tenant::class, $bound = app($this->tenancy->currentTenantContainerKey()));
        $this->assertTrue($tenant->is($bound));
    }

    #[Test]
    public function tenant_can_be_made_current_by_public_method()
    {
        $tenant = Tenant::factory()->create();
        $tenant->makeThisCurrent();

        $this->assertInstanceOf(Tenant::class, $bound = app($this->tenancy->currentTenantContainerKey()));
        $this->assertTrue($tenant->is($bound));
    }

    #[Test]
    public function tenant_can_be_made_current_by_static_method()
    {
        $tenant = Tenant::factory()->create();
        Tenant::makeCurrent($tenant);

        $this->assertInstanceOf(Tenant::class, $bound = app($this->tenancy->currentTenantContainerKey()));
        $this->assertTrue($tenant->is($bound));
    }

    #[Test]
    public function can_get_current_tenant_model_from_container()
    {
        $this->assertFalse(app()->has($key = $this->tenancy->currentTenantContainerKey()));

        $tenant = Tenant::factory()->create();
        $this->tenancy->bindAsCurrentTenant($tenant);

        $this->assertTrue(app()->has($key));
        $bound = Tenant::current();

        $this->assertInstanceOf(Tenant::class, $bound);
        $this->assertTrue($bound->is($tenant));
        $this->assertTrue($bound->is(app($key)));
    }

    #[Test]
    public function can_get_check_if_current_tenant_is_set()
    {
        $this->assertFalse(Tenant::checkCurrent());
        $this->assertFalse($this->tenancy->checkForCurrentTenant());

        $tenant = Tenant::factory()->create();
        $this->tenancy->bindAsCurrentTenant($tenant);

        $this->assertTrue($this->tenancy->checkForCurrentTenant());
        $this->assertTrue(Tenant::checkCurrent());

    }

    #[Test]
    public function can_check_if_a_tenant_is_current()
    {
        $tenant = Tenant::factory()->create();

        $this->assertFalse($tenant->isCurrent());
        $this->assertFalse($this->tenancy->isTenantCurrent($tenant));

        $this->tenancy->bindAsCurrentTenant($tenant);

        $this->assertTrue($this->tenancy->isTenantCurrent($tenant));
        $this->assertTrue($tenant->isCurrent());
    }

    #[Test]
    public function tenant_can_be_forgotten_by_public_method_if_current()
    {
        $tenant = Tenant::factory()->create()->makeThisCurrent();
        $this->assertInstanceOf(Tenant::class, app($key = $this->tenancy->currentTenantContainerKey()));

        $tenant->forgetThis();
        $this->assertFalse(app()->has($key));
    }

    #[Test]
    public function current_tenant_can_by_forgotten_with_static_method()
    {
        $tenant = Tenant::factory()->create()->makeThisCurrent();
        $this->assertInstanceOf(Tenant::class, app($key = $this->tenancy->currentTenantContainerKey()));

        Tenant::forgetCurrent();
        $this->assertFalse(app()->has($key));

    }

    #[Test]
    public function tenant_can_be_made_current_with_filament()
    {
        $tenant = Tenant::factory()->create();

        $this->assertNull(Tenant::current());

        Filament::setTenant($tenant);

        $this->assertGuest();

        $bound = app($this->tenancy->currentTenantContainerKey());

        $this->assertInstanceOf(Tenant::class, $bound);
        $this->assertTrue($tenant->is($bound));
        $this->assertTrue(Tenant::current()->is($bound));
    }

    #[Test]
    public function tenant_can_be_made_current_and_filament_will_follow()
    {
        $tenant = Tenant::factory()->create();

        $this->assertNull(Tenant::current());

        // Fake Filament Serving
        Filament::setServingStatus();

        $tenant->makeThisCurrent();

        $this->assertTrue($tenant->is(Filament::getTenant()));
        $this->assertTrue(Tenant::current()->is(Filament::getTenant()));
    }

    #[Test]
    public function tenant_can_be_switched_and_events_are_fired()
    {
        Event::fake();

        $tenant = Tenant::factory()->create();
        $tenant->makeThisCurrent();

        $this->assertTrue(Tenant::current()->is($tenant));

        $tenant2 = Tenant::factory()->create();
        $tenant2->makeThisCurrent();

        $this->assertFalse(Tenant::current()->is($tenant));
        Tenant::forgetCurrent();

        Event::assertDispatched(Events\MakingTenantCurrentEvent::class);
        Event::assertDispatched(Events\MakingTenantCurrentEvent::class);
        Event::assertDispatched(Events\ForgettingCurrentTenantEvent::class);
        Event::assertDispatched(Events\ForgotCurrentTenantEvent::class);
    }

    #[Test]
    public function finds_tenant_for_web_guard()
    {
        $context = $this->makeUserAndTenant()
            ->authenticate()
            ->create();

        $this->get($context->tenant->url);
        $this->assertTrue($context->tenant->is(Tenant::current()));
    }

    #[Test]
    public function finds_tenant_for_api_guard()
    {
        $context = $this->makeUserAndTenant()
            ->authenticate('api')
            ->create();

        $this->get($context->tenant->url);
        $this->assertTrue($context->tenant->is(Tenant::current()));
    }

    #[Test]
    public function tenant_get_correct_domain_if_using_slug()
    {
        $tenant = Tenant::factory()->create(['slug' => 'test', 'domain' => null])->makeThisCurrent();

        $expectedDomain = 'test.'.tenancy()->mainDomain();
        $expectedUrl = 'https://'.$expectedDomain;

        $this->assertEquals($expectedUrl, Tenant::getUrl($tenant));
        $this->assertEquals($expectedDomain, Tenant::getActiveDomain($tenant));
        $this->assertEquals($expectedDomain, $tenant->active_domain);
        $this->assertNull($tenant->domain);
        $this->assertEquals($expectedUrl, $tenant->url);
    }

    #[Test]
    public function tenant_get_correct_domain_if_custom()
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.co'])->makeThisCurrent();

        $expectedDomain = 'test.co';
        $expectedUrl = 'https://'.$expectedDomain;

        $this->assertEquals($expectedUrl, Tenant::getUrl($tenant));
        $this->assertEquals($expectedDomain, Tenant::getActiveDomain($tenant));
        $this->assertEquals($expectedDomain, $tenant->active_domain);
        $this->assertEquals($expectedUrl, $tenant->domain);
        $this->assertEquals($expectedUrl, $tenant->url);
    }
}
