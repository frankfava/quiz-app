<?php

namespace Database\Seeders;

use App\Models\SuperUser;
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

        // Users
        foreach (range(1, 5) as $index) {
            User::factory()->create([
                'first_name' => ($fn = fake()->firstName),
                'last_name' => ($ln = fake()->lastName),
                'email' => 'user'.$index.'@'.env('APP_DOMAIN'),
            ]);
        }
    }
}
