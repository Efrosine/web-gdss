<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Admin User
        $admin = User::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'position' => null,
        ]);

        // 2. Create Decision Makers
        $dm1 = User::create([
            'name' => 'dm1',
            'email' => 'dm1@example.com',
            'password' => Hash::make('password'),
            'role' => 'decision_maker',
            'position' => 'm1',
        ]);

        $dm2 = User::create([
            'name' => 'dm2',
            'email' => 'dm2@example.com',
            'password' => Hash::make('password'),
            'role' => 'decision_maker',
            'position' => 'm2',
        ]);

        $dm3 = User::create([
            'name' => 'dm3',
            'email' => 'dm3@example.com',
            'password' => Hash::make('password'),
            'role' => 'decision_maker',
            'position' => 'lead',
        ]);

        $dm4 = User::create([
            'name' => 'dm4',
            'email' => 'dm4@example.com',
            'password' => Hash::make('password'),
            'role' => 'decision_maker',
            'position' => 'senior',
        ]);

        $dm5 = User::create([
            'name' => 'dm5',
            'email' => 'dm5@example.com',
            'password' => Hash::make('password'),
            'role' => 'decision_maker',
            'position' => 'expert',
        ]);

        // ==========================================
        // EVENT 1: PKM Event
        // ==========================================
        $event1 = Event::create([
            'event_name' => 'pkm-event',
            'event_date' => '2025-12-31',
            'borda_settings' => null,
        ]);

        // Assign DMs to Event 1 (dm1, dm2, dm3 - dm3 is leader)
        $event1->users()->attach($dm1->id, [
            'is_leader' => false,
            'assigned_at' => now(),
        ]);
        $event1->users()->attach($dm2->id, [
            'is_leader' => false,
            'assigned_at' => now(),
        ]);
        $event1->users()->attach($dm3->id, [
            'is_leader' => true,
            'assigned_at' => now(),
        ]);

        // Create 10 Alternatives for Event 1
        $alternatives1 = collect([
            ['code' => 'a1', 'name' => 'Jovan', 'nip' => '1234567890'],
            ['code' => 'a2', 'name' => 'Fajar', 'nip' => '1234567890'],
            ['code' => 'a3', 'name' => 'Fahmi', 'nip' => '1234567890'],
            ['code' => 'a4', 'name' => 'Abed', 'nip' => '1234567890'],
            ['code' => 'a5', 'name' => 'Kaka', 'nip' => '1234567890'],
            ['code' => 'a6', 'name' => 'Messi', 'nip' => '1234567890'],
            ['code' => 'a7', 'name' => 'Kadimin', 'nip' => '1234567890'],
            ['code' => 'a8', 'name' => 'Sulastri', 'nip' => '1234567890'],
            ['code' => 'a9', 'name' => 'Tuqiyem', 'nip' => '1234567890'],
            ['code' => 'a10', 'name' => 'Bayu', 'nip' => '1234567890'],
        ])->map(fn($alt) => Alternative::create([
                'event_id' => $event1->id,
                'code' => $alt['code'],
                'name' => $alt['name'],
                'nip' => $alt['nip'],
            ]));

        // Create 5 Criteria for Event 1
        $criteria1 = collect([
            ['name' => 'Education level', 'weight' => 5.00, 'attribute_type' => 'benefit'],
            ['name' => 'Academic position', 'weight' => 4.00, 'attribute_type' => 'benefit'],
            ['name' => 'Group tenure', 'weight' => 3.00, 'attribute_type' => 'benefit'],
            ['name' => 'Lecturer certification', 'weight' => 3.00, 'attribute_type' => 'benefit'],
            ['name' => 'Achievement in the field of three pillars of higher education', 'weight' => 5.00, 'attribute_type' => 'benefit'],
        ])->map(fn($crit) => Criterion::create([
                'event_id' => $event1->id,
                'name' => $crit['name'],
                'weight' => $crit['weight'],
                'attribute_type' => $crit['attribute_type'],
            ]));

        // Create Evaluations for Event 1 with exact data from DMs
        // Data preference from DM-1
        $dm1Evaluations = [
            'a1' => [1, 4, 4, 2, 4],  // A1: C1=1, C2=4, C3=4, C4=2, C5=4
            'a2' => [1, 4, 3, 2, 4],  // A2
            'a3' => [1, 4, 4, 2, 4],  // A3
            'a4' => [1, 4, 3, 2, 4],  // A4
            'a5' => [1, 3, 3, 2, 5],  // A5
            'a6' => [1, 2, 3, 2, 4],  // A6
            'a7' => [1, 3, 3, 2, 3],  // A7
            'a8' => [2, 2, 3, 1, 4],  // A8
            'a9' => [1, 2, 3, 2, 2],  // A9
            'a10' => [1, 3, 2, 2, 3], // A10
        ];

        // Data preference from DM-2
        $dm2Evaluations = [
            'a1' => [1, 4, 4, 2, 5],  // A1
            'a2' => [1, 4, 3, 2, 5],  // A2
            'a3' => [1, 4, 4, 2, 3],  // A3
            'a4' => [1, 4, 3, 2, 4],  // A4
            'a5' => [1, 3, 3, 2, 5],  // A5
            'a6' => [1, 2, 3, 2, 5],  // A6
            'a7' => [1, 3, 3, 2, 3],  // A7
            'a8' => [2, 2, 3, 1, 3],  // A8
            'a9' => [1, 2, 3, 2, 2],  // A9
            'a10' => [1, 3, 2, 2, 3], // A10
        ];

        // Data preference from DM-3
        $dm3Evaluations = [
            'a1' => [1, 4, 4, 2, 5],  // A1
            'a2' => [1, 4, 3, 2, 5],  // A2
            'a3' => [1, 4, 4, 2, 4],  // A3
            'a4' => [1, 4, 3, 2, 5],  // A4
            'a5' => [1, 3, 3, 2, 4],  // A5
            'a6' => [1, 2, 3, 2, 4],  // A6
            'a7' => [1, 3, 3, 2, 3],  // A7
            'a8' => [2, 2, 3, 1, 4],  // A8
            'a9' => [1, 2, 3, 2, 3],  // A9
            'a10' => [1, 3, 2, 2, 4], // A10
        ];

        // Create evaluations for DM1
        foreach ($alternatives1 as $alternative) {
            $scores = $dm1Evaluations[$alternative->code];
            foreach ($criteria1 as $index => $criterion) {
                Evaluation::create([
                    'event_id' => $event1->id,
                    'user_id' => $dm1->id,
                    'alternative_id' => $alternative->id,
                    'criterion_id' => $criterion->id,
                    'score_value' => $scores[$index],
                ]);
            }
        }

        // Create evaluations for DM2
        foreach ($alternatives1 as $alternative) {
            $scores = $dm2Evaluations[$alternative->code];
            foreach ($criteria1 as $index => $criterion) {
                Evaluation::create([
                    'event_id' => $event1->id,
                    'user_id' => $dm2->id,
                    'alternative_id' => $alternative->id,
                    'criterion_id' => $criterion->id,
                    'score_value' => $scores[$index],
                ]);
            }
        }

        // Create evaluations for DM3
        foreach ($alternatives1 as $alternative) {
            $scores = $dm3Evaluations[$alternative->code];
            foreach ($criteria1 as $index => $criterion) {
                Evaluation::create([
                    'event_id' => $event1->id,
                    'user_id' => $dm3->id,
                    'alternative_id' => $alternative->id,
                    'criterion_id' => $criterion->id,
                    'score_value' => $scores[$index],
                ]);
            }
        }

        // ==========================================
        // EVENT 2: Innovation Research Funding 2025
        // ==========================================
        $event2 = Event::create([
            'event_name' => 'Innovation Research Funding 2025',
            'event_date' => '2025-11-30',
            'borda_settings' => null,
        ]);

        // Assign DMs to Event 2 (dm1, dm4 - dm1 is leader)
        $event2->users()->attach($dm1->id, [
            'is_leader' => true,
            'assigned_at' => now(),
        ]);
        $event2->users()->attach($dm4->id, [
            'is_leader' => false,
            'assigned_at' => now(),
        ]);

        // Create 8 Alternatives for Event 2
        $alternatives2 = collect([
            ['code' => 'R1', 'name' => 'Dr. Ahmad Reza', 'nip' => '2234567890'],
            ['code' => 'R2', 'name' => 'Prof. Siti Aminah', 'nip' => '2234567891'],
            ['code' => 'R3', 'name' => 'Dr. Budi Santoso', 'nip' => '2234567892'],
            ['code' => 'R4', 'name' => 'Dr. Rina Kartika', 'nip' => '2234567893'],
            ['code' => 'R5', 'name' => 'Prof. Hendra Wijaya', 'nip' => '2234567894'],
            ['code' => 'R6', 'name' => 'Dr. Maya Putri', 'nip' => '2234567895'],
            ['code' => 'R7', 'name' => 'Dr. Farhan Hidayat', 'nip' => '2234567896'],
            ['code' => 'R8', 'name' => 'Prof. Dewi Lestari', 'nip' => '2234567897'],
        ])->map(fn($alt) => Alternative::create([
                'event_id' => $event2->id,
                'code' => $alt['code'],
                'name' => $alt['name'],
                'nip' => $alt['nip'],
            ]));

        // Create 6 Criteria for Event 2
        $criteria2 = collect([
            ['name' => 'Research Novelty', 'weight' => 4.50, 'attribute_type' => 'benefit'],
            ['name' => 'Publication Record', 'weight' => 4.00, 'attribute_type' => 'benefit'],
            ['name' => 'Budget Feasibility', 'weight' => 3.00, 'attribute_type' => 'cost'],
            ['name' => 'Industry Collaboration', 'weight' => 3.50, 'attribute_type' => 'benefit'],
            ['name' => 'Implementation Timeline', 'weight' => 2.50, 'attribute_type' => 'cost'],
            ['name' => 'Social Impact', 'weight' => 4.00, 'attribute_type' => 'benefit'],
        ])->map(fn($crit) => Criterion::create([
                'event_id' => $event2->id,
                'name' => $crit['name'],
                'weight' => $crit['weight'],
                'attribute_type' => $crit['attribute_type'],
            ]));

        // Create Evaluations for Event 2
        foreach ([$dm1, $dm4] as $dm) {
            foreach ($alternatives2 as $alternative) {
                foreach ($criteria2 as $criterion) {
                    Evaluation::create([
                        'event_id' => $event2->id,
                        'user_id' => $dm->id,
                        'alternative_id' => $alternative->id,
                        'criterion_id' => $criterion->id,
                        'score_value' => fake()->randomFloat(2, 1.00, 5.00),
                    ]);
                }
            }
        }

        // ==========================================
        // EVENT 3: Best Lecturer Award 2025
        // ==========================================
        $event3 = Event::create([
            'event_name' => 'Best Lecturer Award 2025',
            'event_date' => '2025-10-15',
            'borda_settings' => null,
        ]);

        // Assign DMs to Event 3 (dm2, dm4, dm5 - dm5 is leader)
        $event3->users()->attach($dm2->id, [
            'is_leader' => false,
            'assigned_at' => now(),
        ]);
        $event3->users()->attach($dm4->id, [
            'is_leader' => false,
            'assigned_at' => now(),
        ]);
        $event3->users()->attach($dm5->id, [
            'is_leader' => true,
            'assigned_at' => now(),
        ]);

        // Create 12 Alternatives for Event 3
        $alternatives3 = collect([
            ['code' => 'L01', 'name' => 'Drs. Agus Setiawan', 'nip' => '3334567890'],
            ['code' => 'L02', 'name' => 'Dr. Fitria Rahmawati', 'nip' => '3334567891'],
            ['code' => 'L03', 'name' => 'Prof. Indra Gunawan', 'nip' => '3334567892'],
            ['code' => 'L04', 'name' => 'Dr. Lina Marlina', 'nip' => '3334567893'],
            ['code' => 'L05', 'name' => 'Drs. Eko Prasetyo', 'nip' => '3334567894'],
            ['code' => 'L06', 'name' => 'Dr. Nurul Hidayah', 'nip' => '3334567895'],
            ['code' => 'L07', 'name' => 'Prof. Bambang Suryadi', 'nip' => '3334567896'],
            ['code' => 'L08', 'name' => 'Dr. Ari Wibowo', 'nip' => '3334567897'],
            ['code' => 'L09', 'name' => 'Drs. Ratna Sari', 'nip' => '3334567898'],
            ['code' => 'L10', 'name' => 'Dr. Hadi Susanto', 'nip' => '3334567899'],
            ['code' => 'L11', 'name' => 'Prof. Sri Mulyani', 'nip' => '3334567900'],
            ['code' => 'L12', 'name' => 'Dr. Rizki Pratama', 'nip' => '3334567901'],
        ])->map(fn($alt) => Alternative::create([
                'event_id' => $event3->id,
                'code' => $alt['code'],
                'name' => $alt['name'],
                'nip' => $alt['nip'],
            ]));

        // Create 7 Criteria for Event 3
        $criteria3 = collect([
            ['name' => 'Teaching Quality', 'weight' => 5.00, 'attribute_type' => 'benefit'],
            ['name' => 'Student Satisfaction', 'weight' => 4.50, 'attribute_type' => 'benefit'],
            ['name' => 'Research Output', 'weight' => 4.00, 'attribute_type' => 'benefit'],
            ['name' => 'Community Service', 'weight' => 3.00, 'attribute_type' => 'benefit'],
            ['name' => 'Innovation in Teaching', 'weight' => 4.00, 'attribute_type' => 'benefit'],
            ['name' => 'Discipline Record', 'weight' => 3.50, 'attribute_type' => 'benefit'],
            ['name' => 'Administrative Task', 'weight' => 2.50, 'attribute_type' => 'benefit'],
        ])->map(fn($crit) => Criterion::create([
                'event_id' => $event3->id,
                'name' => $crit['name'],
                'weight' => $crit['weight'],
                'attribute_type' => $crit['attribute_type'],
            ]));

        // Create Evaluations for Event 3
        foreach ([$dm2, $dm4, $dm5] as $dm) {
            foreach ($alternatives3 as $alternative) {
                foreach ($criteria3 as $criterion) {
                    Evaluation::create([
                        'event_id' => $event3->id,
                        'user_id' => $dm->id,
                        'alternative_id' => $alternative->id,
                        'criterion_id' => $criterion->id,
                        'score_value' => fake()->randomFloat(2, 1.00, 5.00),
                    ]);
                }
            }
        }

        // ==========================================
        // Summary
        // ==========================================
        $this->command->info('Database seeded successfully!');
        $this->command->info('');
        $this->command->info('=== Summary ===');
        $this->command->info('Total Users: 6 (1 admin + 5 decision makers)');
        $this->command->info('');
        $this->command->info('Event 1: pkm-event (Dec 31, 2025)');
        $this->command->info('  - DMs: dm1, dm2, dm3 (leader)');
        $this->command->info('  - Alternatives: 10 (Jovan, Fajar, Fahmi, Abed, Kaka, Messi, Kadimin, Sulastri, Tuqiyem, Bayu)');
        $this->command->info('  - Criteria: 5');
        $this->command->info('  - Evaluations: 150 (3 DMs × 10 alternatives × 5 criteria)');
        $this->command->info('');
        $this->command->info('Event 2: Innovation Research Funding 2025 (Nov 30, 2025)');
        $this->command->info('  - DMs: dm1 (leader), dm4');
        $this->command->info('  - Alternatives: 8');
        $this->command->info('  - Criteria: 6');
        $this->command->info('  - Evaluations: 96 (2 DMs × 8 alternatives × 6 criteria)');
        $this->command->info('');
        $this->command->info('Event 3: Best Lecturer Award 2025 (Oct 15, 2025)');
        $this->command->info('  - DMs: dm2, dm4, dm5 (leader)');
        $this->command->info('  - Alternatives: 12');
        $this->command->info('  - Criteria: 7');
        $this->command->info('  - Evaluations: 252 (3 DMs × 12 alternatives × 7 criteria)');
    }
}
