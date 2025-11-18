<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BordaResult>
 * 
 * Note: This factory is provided for testing purposes only.
 * In production, BordaResult records should be created via DecisionSupportService::calculateBorda()
 */
class BordaResultFactory extends Factory
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
            'alternative_id' => Alternative::factory(),
            'total_borda_points' => fake()->numberBetween(1, 100),
            'final_rank' => fake()->numberBetween(1, 10),
        ];
    }
}
