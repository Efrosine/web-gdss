<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Alternative;
use App\Models\BordaResult;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\User;
use App\Models\WpResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class DecisionSupportService
{
    private const FLOAT_EPS = 1.0e-12;

    /**
     * Check if user can calculate for this event.
     * Admin or event leader can trigger calculation.
     */
    public function canCalculate(int $eventId, int $userId): bool
    {
        $user = User::find($userId);

        if (!$user) {
            return false;
        }

        // Admin can always calculate
        if ($user->isAdmin()) {
            return true;
        }

        // Check if user is leader of this specific event
        return $user->isLeaderOf($eventId);
    }

    /**
     * Get evaluation completeness status for an event.
     * Returns which DMs have completed their evaluations.
     */
    public function getEvaluationCompleteness(int $eventId): array
    {
        $event = Event::with(['decisionMakers', 'alternatives', 'criteria'])
            ->findOrFail($eventId);

        $totalAlternatives = $event->alternatives->count();
        $totalCriteria = $event->criteria->count();
        $expectedEvaluations = $totalAlternatives * $totalCriteria;

        $dmStatus = [];
        $completedDms = 0;

        foreach ($event->decisionMakers as $dm) {
            $actualEvaluations = Evaluation::where('event_id', $eventId)
                ->where('user_id', $dm->id)
                ->count();

            $isComplete = $actualEvaluations === $expectedEvaluations;
            $missingCount = max(0, $expectedEvaluations - $actualEvaluations);

            if ($isComplete) {
                $completedDms++;
            }

            $dmStatus[] = [
                'user_id' => $dm->id,
                'name' => $dm->name,
                'is_leader' => (bool) $dm->pivot->is_leader,
                'is_complete' => $isComplete,
                'actual_evaluations' => $actualEvaluations,
                'expected_evaluations' => $expectedEvaluations,
                'missing_count' => $missingCount,
            ];
        }

        return [
            'is_complete' => $completedDms === $event->decisionMakers->count(),
            'total_dms' => $event->decisionMakers->count(),
            'completed_dms' => $completedDms,
            'dm_status' => $dmStatus,
        ];
    }

    /**
     * Calculate both WP and Borda results for an event.
     * Wrapped in transaction for atomicity.
     */
    public function calculate(int $eventId, ?int $triggeredByUserId = null): void
    {
        // Validate authorization
        if ($triggeredByUserId && !$this->canCalculate($eventId, $triggeredByUserId)) {
            throw new InvalidArgumentException('Unauthorized: Only event leaders or admins can trigger calculation.');
        }

        // Validate completeness
        $completeness = $this->getEvaluationCompleteness($eventId);
        if (!$completeness['is_complete']) {
            throw new InvalidArgumentException('Cannot calculate: Not all decision makers have completed their evaluations.');
        }

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
        $event = Event::with(['decisionMakers', 'alternatives', 'criteria'])->findOrFail($eventId);

        // Validate criteria and weights
        if ($event->criteria->isEmpty()) {
            throw new InvalidArgumentException("Event {$eventId} has no criteria defined.");
        }

        $weightSum = (float) $event->criteria->sum('weight');
        if ($weightSum <= 0.0) {
            throw new InvalidArgumentException("Criteria weights must sum to > 0 for event {$eventId}.");
        }

        // Fetch evaluations and reorganize for O(1) lookup by user:alternative:criterion
        $evaluationsCollection = Evaluation::where('event_id', $eventId)
            ->get();

        $evaluations = [];
        foreach ($evaluationsCollection as $ev) {
            $key = "{$ev->user_id}:{$ev->alternative_id}:{$ev->criterion_id}";
            $evaluations[$key] = $ev;
        }

        $wpData = [];

        foreach ($event->decisionMakers as $user) {
            $userSVectors = [];

            foreach ($event->alternatives as $alternative) {
                $sVector = $this->calculateSVector(
                    $user->id,
                    $alternative->id,
                    $evaluations,
                    $event->criteria,
                    $weightSum
                );

                $userSVectors[] = [
                    'alternative_id' => $alternative->id,
                    's_vector' => $sVector,
                ];
            }

            // Calculate V-vectors (normalize S-vectors)
            $totalS = array_sum(array_column($userSVectors, 's_vector'));

            // Avoid division by zero; if totalS is zero set all v_vector = 0 (shouldn't normally happen if scores > 0)
            $totalS = (float) $totalS;

            foreach ($userSVectors as $data) {
                $wpData[] = [
                    'event_id' => $eventId,
                    'user_id' => $user->id,
                    'alternative_id' => $data['alternative_id'],
                    's_vector' => $data['s_vector'],
                    'v_vector' => $totalS > 0 ? ($data['s_vector'] / $totalS) : 0.0,
                    'individual_rank' => 0, // Will be assigned after sorting
                ];
            }
        }

        // Assign individual ranks per user using Standard Competition Ranking
        $wpData = $this->assignIndividualRanks($wpData);

        // Bulk insert WP results more efficiently
        // Use insert in chunks to avoid large single queries
        $chunks = array_chunk($wpData, 500);
        foreach ($chunks as $chunk) {
            // mutate timestamps if model uses them; here we call create per item to preserve model events,
            // but you may switch to insert(array) if you don't need events/casts.
            foreach ($chunk as $data) {
                WpResult::create($data);
            }
        }
    }

    /**
     * Calculate S-vector for a specific user-alternative combination using log-sum for stability.
     * Formula (log): logS = sum(power * log(score)); S = exp(logS)
     * power = ± normalizedWeight based on attribute_type
     *
     * Throws when a score is non-positive to prevent log/negative-power issues.
     */
    private function calculateSVector(
        int $userId,
        int $alternativeId,
        array $evaluationsLookup,
        $criteria,
        float $weightSum
    ): float {
        $logS = 0.0;

        foreach ($criteria as $criterion) {
            $key = "{$userId}:{$alternativeId}:{$criterion->id}";
            if (!isset($evaluationsLookup[$key])) {
                throw new InvalidArgumentException(
                    "Missing evaluation for user {$userId}, alternative {$alternativeId}, criterion {$criterion->id}"
                );
            }

            $evaluation = $evaluationsLookup[$key];
            $score = (float) $evaluation->score_value;

            // WP requires positive scores because of logs and negative exponents
            if ($score <= 0.0) {
                throw new InvalidArgumentException(
                    "Invalid score for user {$userId}, alternative {$alternativeId}, criterion {$criterion->id}. Score must be > 0."
                );
            }

            $weight = (float) $criterion->weight;
            $normalizedWeight = $weight / $weightSum;

            // Determine power based on attribute type
            $power = $criterion->attribute_type === 'benefit'
                ? abs($normalizedWeight)
                : -abs($normalizedWeight);

            // Use log domain for numerical stability: add power * ln(score)
            $logS += $power * log($score);
        }

        // Convert back to linear domain. exp might overflow in extremal cases, but log-sum reduces risk.
        $sVector = exp($logS);

        // Guard against underflow/NaN/inf — treat any non-finite as zero so normalization still works
        if (!is_finite($sVector) || $sVector < 0.0) {
            // Very small values or overflow fallback
            if (is_infinite($sVector) && $sVector > 0) {
                // extremely large number, clamp to PHP_FLOAT_MAX
                $sVector = PHP_FLOAT_MAX;
            } else {
                $sVector = 0.0;
            }
        }

        return $sVector;
    }

    /**
     * Assign individual ranks to WP results per user using Standard Competition Ranking.
     * If alternatives have same V-vector (within FLOAT_EPS), they get same rank, next rank skips accordingly.
     */
    private function assignIndividualRanks(array $wpData): array
    {
        // Group indices by user
        $grouped = [];
        foreach ($wpData as $index => $data) {
            $grouped[$data['user_id']][] = $index;
        }

        foreach ($grouped as $userId => $indices) {
            // Build array of (index, v_vector) for sorting
            $list = [];
            foreach ($indices as $idx) {
                $list[] = [
                    'index' => $idx,
                    'v_vector' => $wpData[$idx]['v_vector'],
                ];
            }

            // Sort by v_vector DESC
            usort($list, function ($a, $b) {
                if (abs($b['v_vector'] - $a['v_vector']) < self::FLOAT_EPS) {
                    return 0;
                }
                return ($b['v_vector'] > $a['v_vector']) ? 1 : -1;
            });

            $previousV = null;
            foreach ($list as $i => $item) {
                $idx = $item['index'];
                $v = $item['v_vector'];

                if ($previousV !== null && abs($v - $previousV) < self::FLOAT_EPS) {
                    // same rank as previous
                    // keep the already assigned rank (which is same as previous)
                    // do nothing except set individual_rank equal to previous assigned rank
                    // find previous assigned rank by looking at the last non-zero assignment in this user's block
                    // but simpler: look at the previous item in sorted list
                    $prevItem = $list[$i - 1];
                    $wpData[$idx]['individual_rank'] = $wpData[$prevItem['index']]['individual_rank'];
                } else {
                    // new rank is position in sorted list + 1 (standard competition ranking)
                    $rank = $i + 1;
                    $wpData[$idx]['individual_rank'] = $rank;
                }

                $previousV = $v;
            }
        }

        return $wpData;
    }

    /**
     * Calculate Borda aggregation from individual WP rankings using V-vector aggregation.
     * Stores total Borda points and final rank in borda_results table.
     * New Strategy: Sum V-vectors by rank position, then multiply by Borda values.
     */
    private function calculateBordaAggregation(int $eventId): void
    {
        // Fetch event and WP results
        $event = Event::findOrFail($eventId);
        $wpResults = WpResult::where('event_id', $eventId)->get();

        if ($wpResults->isEmpty()) {
            throw new InvalidArgumentException("No WP results found for event {$eventId}.");
        }

        // Count total alternatives
        $totalAlternatives = Alternative::where('event_id', $eventId)->count();
        if ($totalAlternatives <= 0) {
            throw new InvalidArgumentException("Event {$eventId} has no alternatives.");
        }

        // Get custom Borda settings if available
        $bordaSettings = $event->borda_settings;

        // Transform array of objects [{key: "1", value: "100"}, ...] to associative array ["1" => "100", ...]
        if (is_array($bordaSettings) && !empty($bordaSettings)) {
            $firstItem = reset($bordaSettings);
            if (is_array($firstItem) && isset($firstItem['key']) && isset($firstItem['value'])) {
                $transformed = [];
                foreach ($bordaSettings as $item) {
                    if (isset($item['key']) && isset($item['value'])) {
                        $transformed[$item['key']] = $item['value'];
                    }
                }
                $bordaSettings = $transformed;
            }
        }

        if (!is_array($bordaSettings) || empty($bordaSettings)) {
            $bordaSettings = null;
        }

        // Calculate Borda points for each alternative using V-vector aggregation
        $bordaData = [];
        $alternativeIds = $wpResults->pluck('alternative_id')->unique();

        foreach ($alternativeIds as $alternativeId) {
            $totalPoints = 0.0;

            // For each possible rank position (1 to N)
            for ($rank = 1; $rank <= $totalAlternatives; $rank++) {
                // Sum all V-vectors where this alternative received this rank
                $vSum = $wpResults
                    ->where('alternative_id', $alternativeId)
                    ->where('individual_rank', $rank)
                    ->sum('v_vector');

                // Get Borda value for this rank
                if ($bordaSettings && is_array($bordaSettings)) {
                    $rankKey = (string) $rank;
                    if (isset($bordaSettings[$rankKey]) && is_numeric($bordaSettings[$rankKey])) {
                        $bordaValue = (float) $bordaSettings[$rankKey];
                    } else {
                        // Fall back to default formula if rank not in custom settings
                        $bordaValue = (float) ($totalAlternatives - $rank);
                    }
                } else {
                    // Default Borda formula: BordaValue = (Total_Alternatives - Rank)
                    $bordaValue = (float) ($totalAlternatives - $rank);
                }

                // Add to total: VSum × BordaValue
                $totalPoints += ($vSum * $bordaValue);
            }

            $bordaData[] = [
                'event_id' => $eventId,
                'alternative_id' => $alternativeId,
                'total_borda_points' => $totalPoints,
                'final_rank' => 0, // Will be assigned after sorting
            ];
        }

        // Sort by total_borda_points DESC and assign final ranks
        $bordaData = $this->assignFinalRanks($bordaData);

        // Persist results
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
            if ($b['total_borda_points'] === $a['total_borda_points']) {
                return 0;
            }
            return ($b['total_borda_points'] > $a['total_borda_points']) ? 1 : -1;
        });

        $previousPoints = null;
        foreach ($bordaData as $i => &$data) {
            $points = $data['total_borda_points'];

            if ($previousPoints !== null && $points === $previousPoints) {
                // same points => same rank as previous element
                $data['final_rank'] = $bordaData[$i - 1]['final_rank'];
            } else {
                // new rank is position in sorted list + 1
                $data['final_rank'] = $i + 1;
            }

            $previousPoints = $points;
        }

        return $bordaData;
    }

    /**
     * Validate that all required evaluations exist for the event.
     * Expected: decisionMakers × alternatives × criteria evaluations.
     */
    private function validateEvaluationsComplete(int $eventId): void
    {
        $event = Event::withCount(['decisionMakers', 'alternatives', 'criteria'])
            ->findOrFail($eventId);

        $expectedCount = $event->decision_makers_count * $event->alternatives_count * $event->criteria_count;
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
