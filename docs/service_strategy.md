# Service Strategy: DecisionSupportService

## 1. Architecture & Pattern

-   **Namespace:** `App\Services`
-   **Class Name:** `DecisionSupportService`
-   **Strictness:** All methods must use strict typing.
-   **Transaction:** All calculations for an Event must happen inside a `DB::transaction`.
-   **Idempotency:** Before calculating, always `delete()` existing results for the given `event_id` to prevent duplicate data.

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

## 3. Algorithm 2: Borda Aggregation

**Goal:** Combine individual DM ranks into a final group decision.

### Step A: Points Assignment

**Formula:** $Points = (Total\_Alternatives - Rank) + 1$
_(Note: Adjust based on preference. If Rank 1 gets Max Points, use `Total - Rank` or `Total - Rank + 1`)_.

**Logic Rules:**

1. Count distinct `alternative_id` in `wp_results` for this Event ($N$).
2. For each Alternative:
    - Fetch all `individual_rank` values from `wp_results` (from all DMs).
    - Sum the points: $\sum (N - Rank)$.

### Step B: Final Ranking

1. Store sum in `borda_results` (`total_borda_points`).
2. Sort `borda_results` by `total_borda_points` DESCENDING.
3. Assign `final_rank` (1, 2, 3...).
