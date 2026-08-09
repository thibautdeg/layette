<?php

namespace Database\Factories;

use App\Models\BankTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BankTransaction>
 */
class BankTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fingerprint' => fake()->unique()->sha256(),
            'counterparty_name' => fake()->name(),
            'counterparty_account' => 'BE'.fake()->numerify('##############'),
            'description' => fake()->sentence(3),
            'amount' => fake()->numberBetween(5, 100) * 100,
            'booked_at' => now()->toDateString(),
        ];
    }

    public function ignored(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ignored_at' => now(),
        ]);
    }
}
