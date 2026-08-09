<?php

namespace Database\Factories;

use App\Models\NameGuess;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NameGuess>
 */
class NameGuessFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'user_id' => User::factory(),
        ];
    }
}
