<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Plan;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan>
 */
class PlanFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Plan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Plan ' . fake()->numberBetween(1, 999),
            'price' => fake()->numberBetween(1000, 10000),
            'data_limit' => 100,
            'limit_unit' => 'MB',
            'time_limit' => null,
            'speed_limit_upload' => 1024,
            'speed_limit_download' => 2048,
            'validity_days' => 30,
            'is_family' => false,
            'family_limit' => 1, // Default to 1 to satisfy NOT NULL constraint
            'allowed_login_time' => null,
        ];
    }

    /**
     * Indicate that the plan is a family plan.
     */
    public function family(int $limit = 5): static
    {
        return $this->state(fn (array $attributes) => [
            'is_family' => true,
            'family_limit' => $limit,
        ]);
    }

    /**
     * Indicate that the plan has unlimited data.
     */
    public function unlimited(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_limit' => null,
            'limit_unit' => 'Unlimited',
        ]);
    }
}
