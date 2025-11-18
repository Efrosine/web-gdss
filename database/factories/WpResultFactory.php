<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WpResult>
 * 
 * Note: This factory is provided for testing purposes only.
 * In production, WpResult records should be created via DecisionSupportService::calculateWP()
 */
class WpResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_id' => Event::factory(),
            'user_id' => User::factory(),
            'alternative_id' => Alternative::factory(),
            's_vector' => fake()->randomFloat(10, 0.1, 10.0),
            'v_vector' => fake()->randomFloat(10, 0.01, 1.0),
            'individual_rank' => fake()->numberBetween(1, 10),
        ];
    }
}
