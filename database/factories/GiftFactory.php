<?php

namespace Database\Factories;

use App\Models\Gift;
use App\Models\GiftList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gift>
 */
class GiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(10, 300) * 100,
            'shop_url' => fake()->url(),
            'allows_partial_contributions' => true,
            'allows_purchase' => true,
            'position' => 0,
            'gift_list_id' => GiftList::factory(),
        ];
    }

    public function hidden(): static
    {
        return $this->state(fn (array $attributes): array => [
            'hidden_at' => now(),
        ]);
    }

    public function fixedAmount(): static
    {
        return $this->state(fn (array $attributes): array => [
            'allows_partial_contributions' => false,
        ]);
    }

    public function withoutPurchase(): static
    {
        return $this->state(fn (array $attributes): array => [
            'allows_purchase' => false,
        ]);
    }
}
