# 1. Database Schema (Final ERD)

## Master Data

-   **users:** `id`, `username`, `role` ('admin', 'decision_maker')
-   **events:** `id`, `event_name`, `event_date` (Single date)
-   **alternatives:** `id`, `event_id` (FK), `code` (A1), `name`, `nip`
-   **criteria:** `id`, `event_id` (FK), `name`, `weight` (decimal), `attribute_type` ('benefit', 'cost')
-   **event_user:** `id`, `event_id` (FK), `user_id` (FK), `assigned_at` (timestamp) - Pivot table for assigning decision makers to events

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
        - BelongsToMany Users (Assign decision makers to this event via pivot table).
    - _Action:_ "Run Calculation" (Triggers Service).
    - _Note:_ Admin can assign specific decision_maker users to events. Only assigned decision makers can evaluate alternatives for that event.
2. **AlternativeResource:**
    - _Strategy:_ A form that allows managing alternatives scoped to specific events.
    - _Fields:_ Event (Select), Code (Text), Name (Text), NIP (Text).
    - _Filter:_ By Event to view alternatives for specific events.
3. **CriterionResource:**
    - _Strategy:_ A form that allows managing criteria scoped to specific events.
    - _Fields:_ Event (Select), Name (Text), Weight (Decimal), Attribute Type (Select: 'benefit'/'cost').
    - _Filter:_ By Event to view criteria for specific events.
4. **EvaluationResource:**
    - _Strategy:_ A form that filters by Event, then allows scoring that Event's Alternatives against that Event's Criteria.
    - _Cascading Filters:_ Event selection loads only the alternatives and criteria associated with that event.
    - _Access Control:_ Decision makers can only see and evaluate events they are assigned to (via event_user pivot table).
5. **Dashboard:**
    - _Widget:_ `BordaRankingsWidget` (Table showing Final Rank, Name, Total Points).
    - _Filter:_ By Event to view rankings for specific events.
