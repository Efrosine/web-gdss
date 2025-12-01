# 1. Database Schema (Final ERD)

## Master Data

-   **users:** `id`, `username`, `email`, `password`, `role` ('admin', 'decision_maker'), `position` (string, nullable)
-   **events:** `id`, `event_name`, `event_date` (Single date), `borda_settings` (JSON, nullable - stores custom Borda point mapping if leader customizes)
-   **alternatives:** `id`, `event_id` (FK), `code` (A1), `name`, `nip`
-   **criteria:** `id`, `event_id` (FK), `name`, `weight` (decimal), `attribute_type` ('benefit', 'cost')
-   **event_user:** `id`, `event_id` (FK), `user_id` (FK), `is_leader` (boolean, default false), `assigned_at` (timestamp) - Pivot table for assigning decision makers to events and designating per-event leaders

## Transactions (Strict Numeric Scoring)

-   **evaluations:** `id`, `event_id`, `user_id`, `alternative_id`, `criterion_id`, `score_value` (decimal 1-5)

## Results (Calculated)

-   **wp_results:** `id`, `event_id`, `user_id`, `alternative_id`, `s_vector`, `v_vector`, `individual_rank`
-   **borda_results:** `id`, `event_id`, `alternative_id`, `total_borda_points`, `final_rank`

# 2. UI Architecture (Filament Map)

## Resources

1. **EventResource:**
    - _Fields:_ Name (Text), Date (DatePicker).
    - _Relations:_
        - HasMany Alternatives (Manage event-specific alternatives here).
        - HasMany Criteria (Manage weights per event here).
    - _Note:_ Admin-only resource. No direct calculation action here (moved to EventResultsPage).
2. **EventDecisionMakersResource (NEW):**

    - _Purpose:_ Admin-only page/resource to manage decision maker assignments and leader designation per event.
    - _Fields:_ Event (Select), Decision Makers (Multi-Select), Leader Toggle (per DM assigned).
    - _Strategy:_ Manages `event_user` pivot table with `is_leader` flag.
    - _Access Control:_ Admin only.

3. **AlternativeResource:**
    - _Strategy:_ A form that allows managing alternatives scoped to specific events.
    - _Fields:_ Event (Select), Code (Text), Name (Text), NIP (Text).
    - _Filter:_ By Event to view alternatives for specific events.
    - _Access Control:_ Admin only.
4. **CriterionResource:**
    - _Strategy:_ A form that allows managing criteria scoped to specific events.
    - _Fields:_ Event (Select), Name (Text), Weight (Decimal), Attribute Type (Select: 'benefit'/'cost').
    - _Filter:_ By Event to view criteria for specific events.
    - _Access Control:_ Admin only.
5. **EvaluationResource:**
    - _Strategy:_ A form that filters by Event, then allows scoring that Event's Alternatives against that Event's Criteria.
    - _Cascading Filters:_ Event selection loads only the alternatives and criteria associated with that event.
    - _Access Control:_ Decision makers can only see and evaluate events they are assigned to (via event_user pivot table).
6. **UserResource (NEW):**

    - _Purpose:_ Admin-only resource to manage users (name, email, role, position, is_leader).
    - _Fields:_ Name, Email, Role (Select), Position (Text), Is Leader (Toggle).
    - _Access Control:_ Admin only.

7. **EventResultsPage (NEW - Replaces WpResultResource/BordaResultResource):**
    - _Purpose:_ Event-first navigation. Select event, then view merged WP + Borda results.
    - _Workflow:_
        1. **Event Selection:** Dropdown to choose event.
        2. **Evaluation Status (Leader View):** If event never calculated, show list of assigned DMs with completion status (✓ Complete / ✗ Incomplete). Display which DMs have not finished evaluations.
        3. **Borda Settings (Leader Only):** Form to customize Borda point mapping (e.g., Rank 1 → X points, Rank 2 → Y points). Defaults shown. Leader can edit and save to `events.borda_settings` JSON.
        4. **WP Results Table:** Show all DMs' individual V-vectors, S-vectors, and ranks per alternative.
        5. **Borda Results Table:** Show aggregated Borda points and final ranks.
        6. **Calculate Action (Leader/Admin Only):** Button to trigger `DecisionSupportService::calculate()`. Disabled if evaluations incomplete. Leader can recalculate after changing Borda settings.
    - _Access Control:_
        - Decision makers can view results for assigned events (read-only).
        - Event leaders can edit Borda settings and trigger calculation.
        - Admin has full access.

## Dashboard

-   **BordaRankingsWidget (Optional - Keep or Remove):**
    -   _Widget:_ Shows top 10 Borda results across all events (read-only).
    -   _Note:_ May be deprecated in favor of EventResultsPage for better UX.
