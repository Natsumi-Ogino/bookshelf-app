<?php

namespace Database\Factories;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReadingPlan>
 */
class ReadingPlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_date' => fake()->dateTimeBetween('today', '+3 months'),
            'status' => ReadingPlanStatus::InProgress,
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'target_date' => now()->subDay(),
            'status' => ReadingPlanStatus::Expired,
            'completed_at' => null,
        ]);
    }
}
