<?php

namespace Database\Factories;

use App\Enums\QuizStatus;
use App\Models\Quiz;
use App\Models\User;
use Database\Factories\Traits\UseTenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    use UseTenant;

    protected $model = Quiz::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->words(2, true),
            'status' => $this->faker->randomElement(QuizStatus::cases())->value,
            'created_by_id' => User::factory(), // Creates a user if not provided
            'meta' => [],
            'tenant_id' => self::GENERATE_TENANT,
        ];
    }

    /**
     * Configure the model factory.
     *
     * @return Factory
     */
    public function configure()
    {
        return $this->afterMaking(function (Quiz $quiz) {
            $this->setupTenant($quiz);
        });
    }
}
