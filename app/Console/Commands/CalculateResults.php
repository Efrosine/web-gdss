<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\DecisionSupportService;
use Illuminate\Console\Command;

class CalculateResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-results {event_id : The ID of the event to calculate results for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate WP and Borda results for a specific event';

    /**
     * Execute the console command.
     */
    public function handle(DecisionSupportService $service): int
    {
        $eventId = (int) $this->argument('event_id');

        $this->info("Starting calculation for event ID: {$eventId}");

        try {
            $service->calculate($eventId);

            $this->info("✓ Calculation completed successfully!");
            $this->info("Results stored in wp_results and borda_results tables.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("✗ Calculation failed: " . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
