<?php

namespace Tests\Feature\Tenant;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantUserTest extends TestCase
{
    use WithFaker;

    #[Test]
    public function a_tenant_can_have_users()
    {
        $tenant = $this->makeUserAndTenant()
            ->withoutUser()
            ->create()
            ->tenant;

        $this->assertEquals(0, $tenant->users()->count());

        $tenant->users()->save(User::factory()->make());

        $this->assertEquals(1, $tenant->users()->count());
    }

    #[Test]
    public function when_a_user_is_attached_to_tenant_an_event_is_fired()
    {
        Event::fake(['UserWasAddedToTenant']);

        $this->makeUserAndTenant()->create();

        Event::assertDispatched('UserWasAddedToTenant');
    }

    #[Test]
    public function a_user_can_access_their_tenant()
    {
        $tenant = $this->makeUserAndTenant()
            ->create()
            ->tenant;

        $tenant->users()->save($user = User::factory()->make());

        $user2 = User::factory()->make();

        $this->assertTrue($user->canAccessTenant($tenant));
        $this->assertFalse($user2->canAccessTenant($tenant));
    }

    #[Test]
    public function user_is_removed_from_tenant_when_deleted()
    {
        $context = $this->makeUserAndTenant()
            ->authenticate()
            ->create();

        $user = $context->user;
        $tenant = $context->tenant;

        Event::fake(['UserWasRemovedFromTenant']);

        $this->assertTrue($tenant->users()->whereId($user->id)->exists());
        $user->delete();
        $this->assertFalse($tenant->users()->whereId($user->id)->exists());

        Event::assertDispatched('UserWasRemovedFromTenant');
    }
}
