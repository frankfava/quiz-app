<?php

namespace Tests\Feature;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_be_created_with_all_fields(): void
    {
        $tenantData = [
            'name' => 'Test School',
            'slug' => 'test-school',
            'domain' => 'test-school.test',
            'foc' => true,
        ];

        Tenant::create($tenantData);

        $this->assertDatabaseHas('tenants', $tenantData);
    }

    public function test_tenant_generates_slug_from_name_if_not_provided(): void
    {
        $tenantData = [
            'name' => 'No Slug School',
            'foc' => false,
        ];

        Tenant::create($tenantData);

        $this->assertDatabaseHas('tenants', [
            'name' => 'No Slug School',
            'slug' => 'test-no-slug-school', // Adjusted for testing env
            'domain' => null,
            'foc' => false,
        ]);
    }
}
