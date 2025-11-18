<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Evaluation>
 */
class EvaluationFactory extends Factory
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
            'criterion_id' => Criterion::factory(),
            'score_value' => fake()->randomFloat(2, 1.00, 5.00),
        ];
    }
}
