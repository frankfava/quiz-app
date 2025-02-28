<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class TenantFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $company = $this->faker->unique()->company();

        return [
            'name' => $company,
            'slug' => null,
            'domain' => $this->faker->boolean(30) ? str($company)->slug().'.'.$this->faker->tld() : null,
            'foc' => $this->faker->boolean(),
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return Factory
     */
    public function configure()
    {
        return $this->afterMaking(function (Tenant $tenant) {
            if (! $tenant->slug) {
                $tenant->slug = (config('app.env', 'local') == 'testing' ? 'test-' : '').str($tenant->name)->slug()->toString();
            }
        });
    }
}
