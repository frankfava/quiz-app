<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Tenants
        $knownTenant1 = Tenant::factory()->create([
            'name' => 'John and Jane',
            'slug' => 'futuresmiths',
            'domain' => null,
            'foc' => true,
        ]);
        $knownTenant2 = Tenant::factory()->create([
            'name' => 'Custom Domain Tenant',
            'domain' => str(env('APP_NAME').'-tenant.')->slug().'.test',
            'foc' => true,
        ]);
        Tenant::factory(3)->create();
    }
}
