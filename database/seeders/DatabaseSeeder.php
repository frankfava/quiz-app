<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\SuperUser;
use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    const MAX_USERS_PER_TENANT = 1;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Superuser
        SuperUser::factory()->create([
            'name' => 'Frank Fava',
            'email' => 'frank.fava@gmail.com',
        ]);
        SuperUser::factory()->create([
            'name' => 'Super User',
            'email' => 'superuser@'.env('APP_DOMAIN'),
        ]);

        // Tenants
        $knownTenant1 = Tenant::factory()->create([
            'name' => 'Shawn Spencer',
            'slug' => 'shawn',
            'domain' => null,
            'foc' => true,
        ]);
        $knownTenant2 = Tenant::factory()->create([
            'name' => 'Custom Domain Tenant',
            'domain' => str(env('APP_NAME').'-tenant.')->slug().'.test',
            'foc' => true,
        ]);
        Tenant::factory(3)->create();

        // Users
        foreach (range(1, 5) as $index) {
            User::factory()->create([
                'first_name' => ($fn = fake()->firstName),
                'last_name' => ($ln = fake()->lastName),
                'email' => 'user'.$index.'@'.env('APP_DOMAIN'),
            ]);
        }

        $tenants = Tenant::all();

        $knownTenantUserEmail = 'user@'.env('APP_DOMAIN');
        $knownTenantUser = User::withoutGlobalScope(Tenant::class)->whereEmail($knownTenantUserEmail)->first();
        if (! $knownTenantUser) {
            $knownTenantUser = User::factory()->create([
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $knownTenantUserEmail,
            ]);
        }

        $tenantCountPerUser = config('tenancy.restrict_to_tenant_count_per_user');
        $userCountPerTenant = config('tenancy.restrict_to_user_count_per_tenant');

        if ($knownTenantUser) {
            config(['tenancy.restrict_to_tenant_count_per_user' => null]);
            config(['tenancy.restrict_to_user_count_per_tenant' => null]);

            $knownTenantUser->tenants()->sync($tenants->pluck('id')->toArray(), ['role' => UserRole::ADMIN], true);

            config(['tenancy.restrict_to_tenant_count_per_user' => $tenantCountPerUser]);
            config(['tenancy.restrict_to_user_count_per_tenant' => $userCountPerTenant]);
        }

        // Tenant Users
        $tenants->each(function ($tenant) use ($knownTenantUserEmail, $userCountPerTenant) {
            $tenant->makeThisCurrent();

            if ((int) $userCountPerTenant > 1) {
                $users = User::withoutGlobalScope(Tenant::class)->where('email', '!=', $knownTenantUserEmail)->get();
                $users = $users->take(rand(1, max((int) $userCountPerTenant, $users->count())));

                $users->each(function ($user) use ($tenant) {
                    $user->tenants()->attach($tenant, ['role' => UserRole::ADMIN], true);
                });
            }
        });
    }
}
