<?php

namespace Database\Factories;

use App\Enums\ContributionStatus;
use App\Enums\ContributionType;
use App\Models\Contribution;
use App\Models\Gift;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contribution>
 */
class ContributionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Contribution::generateReference(),
            'type' => ContributionType::Contribution,
            'status' => ContributionStatus::Pending,
            'name' => fake()->name(),
            'amount' => fake()->numberBetween(5, 100) * 100,
            'message' => fake()->boolean(60) ? fake()->sentence() : null,
            'gift_id' => Gift::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContributionStatus::Confirmed,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => ContributionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function purchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => ContributionType::Purchase,
            'amount' => null,
        ]);
    }
}
