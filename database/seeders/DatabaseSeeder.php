<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        // 2. Create Decision Makers
        $decisionMakers = User::factory()->count(3)->create();

        // 3. Create Events
        $event1 = Event::factory()->create([
            'event_name' => 'PKM Competition 2025',
            'event_date' => '2025-12-15',
        ]);

        $event2 = Event::factory()->create([
            'event_name' => 'Innovation Grant 2025',
            'event_date' => '2025-11-30',
        ]);

        // 4. Assign Decision Makers to Events (with leader designation)
        // Event 1: All 3 DMs, first one is leader
        foreach ($decisionMakers as $index => $dm) {
            $event1->users()->attach($dm->id, [
                'is_leader' => $index === 0, // First DM is leader
                'assigned_at' => now(),
            ]);
        }

        // Event 2: First 2 DMs, first one is leader
        foreach ($decisionMakers->take(2) as $index => $dm) {
            $event2->users()->attach($dm->id, [
                'is_leader' => $index === 0, // First DM is leader
                'assigned_at' => now(),
            ]);
        }

        // 5. Create Alternatives for Event 1
        $alternatives1 = collect([
            ['code' => 'A1', 'name' => 'John Doe', 'nip' => '1234567890'],
            ['code' => 'A2', 'name' => 'Jane Smith', 'nip' => '0987654321'],
            ['code' => 'A3', 'name' => 'Bob Johnson', 'nip' => '1122334455'],
            ['code' => 'A4', 'name' => 'Alice Brown', 'nip' => '5544332211'],
        ])->map(fn($alt) => Alternative::factory()->create([
                'event_id' => $event1->id,
                'code' => $alt['code'],
                'name' => $alt['name'],
                'nip' => $alt['nip'],
            ]));

        // 6. Create Alternatives for Event 2
        $alternatives2 = collect([
            ['code' => 'A1', 'name' => 'Charlie Wilson', 'nip' => '2233445566'],
            ['code' => 'A2', 'name' => 'Diana Prince', 'nip' => '6655443322'],
            ['code' => 'A3', 'name' => 'Eve Adams', 'nip' => '7788990011'],
        ])->map(fn($alt) => Alternative::factory()->create([
                'event_id' => $event2->id,
                'code' => $alt['code'],
                'name' => $alt['name'],
                'nip' => $alt['nip'],
            ]));

        // 7. Create Criteria for Event 1 (weights must sum to appropriate values)
        $criteria1 = collect([
            ['name' => 'Academic Achievement', 'weight' => 0.30, 'attribute_type' => 'benefit'],
            ['name' => 'Innovation Level', 'weight' => 0.25, 'attribute_type' => 'benefit'],
            ['name' => 'Budget Efficiency', 'weight' => 0.20, 'attribute_type' => 'cost'],
            ['name' => 'Team Collaboration', 'weight' => 0.15, 'attribute_type' => 'benefit'],
            ['name' => 'Implementation Time', 'weight' => 0.10, 'attribute_type' => 'cost'],
        ])->map(fn($crit) => Criterion::factory()->create([
                'event_id' => $event1->id,
                'name' => $crit['name'],
                'weight' => $crit['weight'],
                'attribute_type' => $crit['attribute_type'],
            ]));

        // 8. Create Criteria for Event 2
        $criteria2 = collect([
            ['name' => 'Research Quality', 'weight' => 0.35, 'attribute_type' => 'benefit'],
            ['name' => 'Market Potential', 'weight' => 0.30, 'attribute_type' => 'benefit'],
            ['name' => 'Development Cost', 'weight' => 0.20, 'attribute_type' => 'cost'],
            ['name' => 'Risk Level', 'weight' => 0.15, 'attribute_type' => 'cost'],
        ])->map(fn($crit) => Criterion::factory()->create([
                'event_id' => $event2->id,
                'name' => $crit['name'],
                'weight' => $crit['weight'],
                'attribute_type' => $crit['attribute_type'],
            ]));

        // 9. Create Evaluations for Event 1
        // Each decision maker evaluates each alternative against each criterion
        foreach ($decisionMakers as $user) {
            foreach ($alternatives1 as $alternative) {
                foreach ($criteria1 as $criterion) {
                    Evaluation::factory()->create([
                        'event_id' => $event1->id,
                        'user_id' => $user->id,
                        'alternative_id' => $alternative->id,
                        'criterion_id' => $criterion->id,
                        'score_value' => fake()->randomFloat(2, 1.00, 5.00),
                    ]);
                }
            }
        }

        // 10. Create Evaluations for Event 2
        foreach ($decisionMakers->take(2) as $user) {
            foreach ($alternatives2 as $alternative) {
                foreach ($criteria2 as $criterion) {
                    Evaluation::factory()->create([
                        'event_id' => $event2->id,
                        'user_id' => $user->id,
                        'alternative_id' => $alternative->id,
                        'criterion_id' => $criterion->id,
                        'score_value' => fake()->randomFloat(2, 1.00, 5.00),
                    ]);
                }
            }
        }

        // Note: WpResults and BordaResults are NOT seeded here
        // They will be calculated via DecisionSupportService
        // Run: php artisan app:calculate-results {event_id}

        $this->command->info('Database seeded successfully!');
        $this->command->info('To calculate results, run: php artisan app:calculate-results {event_id}');
    }
}
