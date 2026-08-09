<?php

namespace Database\Factories;

use App\Models\GiftList;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GiftList>
 */
class GiftListFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => Str::random(24),
            'title' => fake()->sentence(3),
            'intro' => fake()->paragraph(),
            'iban' => 'BE68539007547034',
            'account_holder' => fake()->name(),
            'wero_phone' => '+32470123456',
            'expected_at' => now()->addMonths(3)->toDateString(),
        ];
    }

    public function closed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'closed_at' => now(),
        ]);
    }
}
