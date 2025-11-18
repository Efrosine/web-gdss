<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alternative;
use App\Models\BordaResult;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\WpResult;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DecisionSupportService
{
    /**
     * Calculate both WP and Borda results for an event.
     * Wrapped in transaction for atomicity.
     */
    public function calculate(int $eventId): void
    {
        DB::transaction(function () use ($eventId) {
            // Validate event exists
            $event = Event::findOrFail($eventId);

            // Validate evaluations are complete
            $this->validateEvaluationsComplete($eventId);

            // Delete existing results for idempotency
            WpResult::where('event_id', $eventId)->delete();
            BordaResult::where('event_id', $eventId)->delete();

            // Calculate WP method (individual rankings)
            $this->calculateWeightedProduct($eventId);

            // Calculate Borda aggregation (final group ranking)
            $this->calculateBordaAggregation($eventId);
        });
    }

    /**
     * Calculate Weighted Product (WP) results for each user-alternative combination.
     * Stores S-vector, V-vector, and individual rank in wp_results table.
     */
    private function calculateWeightedProduct(int $eventId): void
    {
        // Fetch all required data
        $event = Event::with(['users', 'alternatives', 'criteria'])->findOrFail($eventId);
        $evaluations = Evaluation::where('event_id', $eventId)
            ->with(['criterion'])
            ->get()
            ->groupBy(['user_id', 'alternative_id']);

        // Calculate S-vectors for each user-alternative combination
        $wpData = [];

        foreach ($event->users as $user) {
            $userSVectors = [];

            foreach ($event->alternatives as $alternative) {
                $sVector = $this->calculateSVector(
                    $user->id,
                    $alternative->id,
                    $evaluations,
                    $event->criteria
                );

                $userSVectors[] = [
                    'alternative_id' => $alternative->id,
                    's_vector' => $sVector,
                ];
            }

            // Calculate V-vectors (normalize S-vectors)
            $totalS = array_sum(array_column($userSVectors, 's_vector'));

            foreach ($userSVectors as $data) {
                $wpData[] = [
                    'event_id' => $eventId,
                    'user_id' => $user->id,
                    'alternative_id' => $data['alternative_id'],
                    's_vector' => $data['s_vector'],
                    'v_vector' => $totalS > 0 ? $data['s_vector'] / $totalS : 0,
                    'individual_rank' => 0, // Will be assigned after sorting
                ];
            }
        }

        // Assign individual ranks per user using Standard Competition Ranking
        $wpData = $this->assignIndividualRanks($wpData);

        // Bulk insert WP results
        foreach ($wpData as $data) {
            WpResult::create($data);
        }
    }

    /**
     * Calculate S-vector for a specific user-alternative combination.
     * Formula: S = Product of (score^power) where power = ±weight based on attribute_type
     */
    private function calculateSVector(
        int $userId,
        int $alternativeId,
        $evaluations,
        $criteria
    ): float {
        $sVector = 1.0;

        foreach ($criteria as $criterion) {
            $evaluation = $evaluations[$userId][$alternativeId]
                ->firstWhere('criterion_id', $criterion->id);

            if (!$evaluation) {
                throw new InvalidArgumentException(
                    "Missing evaluation for user {$userId}, alternative {$alternativeId}, criterion {$criterion->id}"
                );
            }

            $score = (float) $evaluation->score_value;
            $weight = (float) $criterion->weight;

            // Determine power based on attribute type
            $power = $criterion->attribute_type === 'benefit'
                ? abs($weight)
                : -abs($weight);

            // Calculate score^power
            $sVector *= pow($score, $power);
        }

        return $sVector;
    }

    /**
     * Assign individual ranks to WP results per user using Standard Competition Ranking.
     * If alternatives have same V-vector, they get same rank, next rank skips accordingly.
     */
    private function assignIndividualRanks(array $wpData): array
    {
        // Group by user
        $grouped = [];
        foreach ($wpData as $index => $data) {
            $grouped[$data['user_id']][] = ['index' => $index, 'data' => $data];
        }

        // Rank each user's alternatives
        foreach ($grouped as $userId => $userAlternatives) {
            // Sort by v_vector DESC
            usort($userAlternatives, function ($a, $b) {
                return $b['data']['v_vector'] <=> $a['data']['v_vector'];
            });

            // Assign ranks with Standard Competition Ranking (1-2-2-4)
            $currentRank = 1;
            $previousVVector = null;
            $sameRankCount = 0;

            foreach ($userAlternatives as $i => $item) {
                $vVector = $item['data']['v_vector'];

                if ($previousVVector !== null && abs($vVector - $previousVVector) < 0.0000000001) {
                    // Same value, assign same rank
                    $wpData[$item['index']]['individual_rank'] = $currentRank;
                    $sameRankCount++;
                } else {
                    // Different value, assign new rank
                    if ($sameRankCount > 0) {
                        $currentRank += $sameRankCount + 1;
                    } else {
                        $currentRank = $i + 1;
                    }
                    $wpData[$item['index']]['individual_rank'] = $currentRank;
                    $sameRankCount = 0;
                }

                $previousVVector = $vVector;
            }
        }

        return $wpData;
    }

    /**
     * Calculate Borda aggregation from individual WP rankings.
     * Stores total Borda points and final rank in borda_results table.
     */
    private function calculateBordaAggregation(int $eventId): void
    {
        // Fetch WP results
        $wpResults = WpResult::where('event_id', $eventId)->get();

        // Count total alternatives
        $totalAlternatives = Alternative::where('event_id', $eventId)->count();

        // Calculate Borda points for each alternative
        $bordaData = [];
        $alternativeIds = $wpResults->pluck('alternative_id')->unique();

        foreach ($alternativeIds as $alternativeId) {
            $ranks = $wpResults->where('alternative_id', $alternativeId)
                ->pluck('individual_rank');

            // Borda formula: Points = (Total_Alternatives - Rank)
            $totalPoints = $ranks->sum(function ($rank) use ($totalAlternatives) {
                return $totalAlternatives - $rank;
            });

            $bordaData[] = [
                'event_id' => $eventId,
                'alternative_id' => $alternativeId,
                'total_borda_points' => $totalPoints,
                'final_rank' => 0, // Will be assigned after sorting
            ];
        }

        // Sort by total_borda_points DESC and assign final ranks
        $bordaData = $this->assignFinalRanks($bordaData);

        // Bulk insert Borda results
        foreach ($bordaData as $data) {
            BordaResult::create($data);
        }
    }

    /**
     * Assign final ranks using Standard Competition Ranking (1-2-2-4).
     */
    private function assignFinalRanks(array $bordaData): array
    {
        // Sort by total_borda_points DESC
        usort($bordaData, function ($a, $b) {
            return $b['total_borda_points'] <=> $a['total_borda_points'];
        });

        // Assign ranks with Standard Competition Ranking
        $currentRank = 1;
        $previousPoints = null;
        $sameRankCount = 0;

        foreach ($bordaData as $i => &$data) {
            $points = $data['total_borda_points'];

            if ($previousPoints !== null && $points === $previousPoints) {
                // Same points, assign same rank
                $data['final_rank'] = $currentRank;
                $sameRankCount++;
            } else {
                // Different points, assign new rank
                if ($sameRankCount > 0) {
                    $currentRank += $sameRankCount + 1;
                } else {
                    $currentRank = $i + 1;
                }
                $data['final_rank'] = $currentRank;
                $sameRankCount = 0;
            }

            $previousPoints = $points;
        }

        return $bordaData;
    }

    /**
     * Validate that all required evaluations exist for the event.
     * Expected: users × alternatives × criteria evaluations.
     */
    private function validateEvaluationsComplete(int $eventId): void
    {
        $event = Event::withCount(['users', 'alternatives', 'criteria'])
            ->findOrFail($eventId);

        $expectedCount = $event->users_count * $event->alternatives_count * $event->criteria_count;
        $actualCount = Evaluation::where('event_id', $eventId)->count();

        if ($actualCount !== $expectedCount) {
            throw new InvalidArgumentException(
                "Incomplete evaluations for event {$eventId}. " .
                "Expected: {$expectedCount}, Found: {$actualCount}. " .
                "All decision makers must evaluate all alternatives against all criteria."
            );
        }
    }
}
