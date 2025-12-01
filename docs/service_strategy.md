# Service Strategy: DecisionSupportService

## 1. Architecture & Pattern

-   **Namespace:** `App\Services`
-   **Class Name:** `DecisionSupportService`
-   **Strictness:** All methods must use strict typing.
-   **Transaction:** All calculations for an Event must happen inside a `DB::transaction`.
-   **Idempotency:** Before calculating, always `delete()` existing results for the given `event_id` to prevent duplicate data.
-   **Leader Customization:** Service must support dynamic Borda point mapping from `events.borda_settings` JSON or use default formula.

## 2. Algorithm 1: Weighted Product (WP)

**Goal:** Calculate a preference value (V) for each Alternative per Decision Maker (DM).

### Step A: S-Vector Calculation

For each **User (u)** and **Alternative (a)** in the Event:
$$S_{ua} = \prod_{j=1}^{n} (Score_{uaj})^{P_j}$$

**Logic Rules:**

1. Fetch all `Evaluation` rows for this `event_id`.
2. **The Exponent ($P_j$):**
    - Fetch `Criterion` weight ($W_j$).
    - IF `attribute_type` == 'benefit' $\rightarrow$ $P_j = |W_j|$
    - IF `attribute_type` == 'cost' $\rightarrow$ $P_j = -|W_j|$ (Negative Power)
3. **Handling Zeros:** If a score is 0, standard WP fails. Defaults to 1 or handling edge cases is required (but based on your 1-5 scale, this shouldn't happen).

### Step B: V-Vector Calculation (Relative Preference)

$$V_{ua} = \frac{S_{ua}}{\sum_{all\_alternatives} S_{u}}$$

**Logic Rules:**

1. Sum all $S$ values calculated in Step A for that specific User.
2. Divide individual $S$ by Total $S$.
3. Store result in `wp_results` table: `s_vector`, `v_vector`.

### Step C: Individual Ranking

-   Sort `wp_results` by `v_vector` DESCENDING.
-   Assign Rank 1 to the highest V.

---

## 3. Algorithm 2: Borda Aggregation (with Leader Customization)

**Goal:** Combine individual DM ranks into a final group decision.

### Step A: Points Assignment (Dynamic Formula)

**Default Formula:** $Points = (Total\_Alternatives - Rank)$

**Leader Override:** If `events.borda_settings` JSON exists, use custom point mapping:

```json
{
    "1": 10,
    "2": 7,
    "3": 5,
    "4": 2,
    "5": 1
}
```

_(Where key = rank, value = points)_

**Logic Rules:**

1. Fetch `events.borda_settings` for the event. If NULL, use default formula.
2. Count distinct `alternative_id` in `wp_results` for this Event ($N$).
3. For each Alternative:
    - Fetch all `individual_rank` values from `wp_results` (from all DMs).
    - **IF custom settings exist:** Sum points using mapping: $\sum BordaSettings[Rank]$.
    - **ELSE:** Sum points using default: $\sum (N - Rank)$.

### Step B: Final Ranking

1. Store sum in `borda_results` (`total_borda_points`).
2. Sort `borda_results` by `total_borda_points` DESCENDING.
3. Assign `final_rank` (1, 2, 3...).

---

## 4. New Service Methods (Leader Support)

### `getEvaluationCompleteness(int $eventId): array`

**Purpose:** Check which DMs have completed evaluations for an event.

**Returns:**

```php
[
    'is_complete' => bool,
    'total_dms' => int,
    'completed_dms' => int,
    'dm_status' => [
        ['user_id' => 1, 'name' => 'John', 'is_complete' => true, 'missing_count' => 0],
        ['user_id' => 2, 'name' => 'Jane', 'is_complete' => false, 'missing_count' => 5],
    ]
]
```

**Logic:**

1. Fetch all assigned DMs from `event_user` pivot for the event.
2. Calculate expected evaluations: `count(alternatives) × count(criteria)` per DM.
3. Count actual evaluations per DM from `evaluations` table.
4. Compare and return status.

### `calculate(int $eventId, ?int $triggeredByUserId = null): void`

**Purpose:** Run full WP + Borda calculation. Now accepts optional `triggeredByUserId` for audit logs (future enhancement).

**Pre-Calculation Validation:**

1. Call `getEvaluationCompleteness()`. Throw exception if not complete.
2. Check if triggered by event leader or admin (authorization handled in controller/page).

**Calculation Steps:**

1. Delete existing `wp_results` and `borda_results` for event.
2. Run WP algorithm (S-vector, V-vector, individual ranks).
3. Fetch `events.borda_settings` (if exists).
4. Run Borda aggregation with custom or default points.
5. Store results in transaction.

### `previewResults(int $eventId): array` (Optional)

**Purpose:** Generate WP results in memory without persisting. Useful for leader preview before finalizing.

**Returns:** Array of WP results and Borda preview (not stored in DB).

---

## 5. Idempotency & Transaction Safety

-   **Always** wrap full calculation in `DB::transaction()`.
-   **Always** delete old results before inserting new ones to prevent duplicates.
-   **Rollback** on any exception during calculation.

---

## 6. Edge Cases

1. **Zero Scores:** If `score_value` is 0, WP formula fails. Handle by treating 0 as 1 (or throw validation error during evaluation entry).
2. **Missing Evaluations:** `calculate()` must fail if any DM has incomplete evaluations. Use `getEvaluationCompleteness()` to validate.
3. **Custom Borda Missing Ranks:** If leader provides custom Borda settings but omits a rank (e.g., only defines ranks 1-3 but there are 5 alternatives), fall back to default formula for undefined ranks or throw validation error.
