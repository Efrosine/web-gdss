# Project Profile

-   **Goal:** Build a Group Decision Support System (GDSS) for "Student Creativity Program" (PKM).
-   **Stack:** Laravel 12, Filament 4, MySQL, PHP 8.2+.
-   **Methodology:** Weighted Product (WP) -> Borda Aggregation.
-   **Roles:** Admin (full access), Decision Maker (evaluate assigned events), Event Leader (DM with calculation control per event).

# Workflow Rules (STRICT)

1. **CLI First:** Do not generate Model/Resource code manually. ALWAYS provide the `php artisan` command first.
    - _Good:_ "Run `php artisan make:filament-resource Event`"
    - _Bad:_ "Here is the code for EventResource.php..."
2. **Service Pattern:** All calculation logic goes into `App/Services/DecisionSupportService.php`.
3. **Strict Types:** Use `declare(strict_types=1);` in all PHP files.

# Mathematical Rules (The Paper)

1. **WP (S-Vector):** `Product(Score ^ Weight)`.
    - IF Attribute = Benefit: `Power = abs(Weight)`
    - IF Attribute = Cost: `Power = -abs(Weight)`
2. **Borda:** Points based on Rank. Default: `Points = (Total_Alternatives - Rank)`.
    - **Leader Customization:** Event leaders can override default Borda point mapping before calculation. Custom settings stored in `events.borda_settings` (JSON).

# UI Strategy (Filament 4)

-   **Input:** Use `Filament\Forms`. For the "Evaluation" matrix (User x Criteria), suggest a Repeater or a Custom View if standard forms are too rigid.
-   **Output:** Custom `EventResultsPage` shows WP and Borda results after selecting an event. Event-first navigation flow.
-   **Actions:** Place "Calculate" buttons accessible only to Event Leaders or Admin. Leader must verify all DMs completed evaluations before calculation.
-   **DM Assignment:** Dedicated admin-only page/resource for assigning decision makers to events and designating leaders per event.
