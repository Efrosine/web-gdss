<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Event;
use App\Models\User;
use App\Models\WpResult;
use App\Services\DecisionSupportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class EventResults extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Results';

    protected static ?string $navigationLabel = 'Results Dashboard';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.event-results';

    public ?int $selectedEventId = null;
    public ?int $userId = null;
    public ?array $bordaSettings = null;
    public ?array $completenessData = null;
    public bool $canManage = false;
    public ?array $bordaMatrix = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedEventId')
                    ->label('Select Event')
                    ->options(function () {
                        $user = Auth::user();
                        if ($user->isAdmin()) {
                            return Event::pluck('event_name', 'id');
                        }
                        // Decision makers see only assigned events
                        return $user->events()->pluck('event_name', 'event_id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedEventId = $state;
                        $this->userId = null; // Reset user when event changes
                        $this->loadEventData();
                    })
                    ->required(),

                Select::make('userId')
                    ->label('Decision Maker (Optional)')
                    ->options(function () {
                        if (!$this->selectedEventId) {
                            return [];
                        }

                        // Get users who have WP results for this event
                        $query = User::query()
                            ->whereHas('wpResults', function ($q) {
                            $q->where('event_id', $this->selectedEventId);
                        });

                        return $query->pluck('name', 'id');
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->placeholder('Select to view individual WP matrix')
                    ->disabled(fn() => !$this->selectedEventId)
                    ->afterStateUpdated(fn($state) => $this->userId = $state)
                    ->helperText('Leave empty to view only aggregated Borda results')
                    ->columnSpanFull(),

                KeyValue::make('bordaSettings')
                    ->label('Custom Borda Points (Rank → Points)')
                    ->keyLabel('Rank')
                    ->valueLabel('Points')
                    ->addButtonLabel('Add Rank Points')
                    ->visible(fn() => $this->canManage && $this->selectedEventId)
                    ->helperText('Define custom points for each rank. Leave empty to use default formula: (Total Alternatives - Rank)')
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('saveBordaSettings')
                ->label('Save Borda Settings')
                ->icon('heroicon-o-cog-6-tooth')
                ->color('info')
                ->visible(fn() => $this->canManage && $this->selectedEventId)
                ->action(function () {
                    if (!$this->selectedEventId) {
                        return;
                    }

                    $event = Event::find($this->selectedEventId);
                    $event->borda_settings = $this->bordaSettings;
                    $event->save();

                    Notification::make()
                        ->title('Borda settings saved successfully')
                        ->success()
                        ->send();
                }),

            Action::make('calculate')
                ->label('Calculate Results')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->visible(fn() => $this->canManage && $this->selectedEventId)
                ->requiresConfirmation()
                ->modalHeading('Calculate Rankings')
                ->modalDescription(function () {
                    if (!$this->completenessData || !$this->completenessData['is_complete']) {
                        return 'Warning: Not all decision makers have completed their evaluations. Calculation will fail.';
                    }
                    return 'This will recalculate WP and Borda results for this event. Previous results will be overwritten.';
                })
                ->modalSubmitActionLabel('Calculate')
                ->action(function (DecisionSupportService $service) {
                    if (!$this->selectedEventId) {
                        return;
                    }

                    try {
                        $service->calculate($this->selectedEventId, Auth::id());

                        Notification::make()
                            ->title('Calculation completed successfully')
                            ->success()
                            ->send();

                        $this->loadEventData();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Calculation failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function loadEventData(): void
    {
        if (!$this->selectedEventId) {
            $this->completenessData = null;
            $this->canManage = false;
            $this->bordaSettings = null;
            $this->bordaMatrix = null;
            return;
        }

        $event = Event::find($this->selectedEventId);
        $user = Auth::user();

        // Check if user can manage (admin or leader)
        $this->canManage = $user->isAdmin() || $user->isLeaderOf($this->selectedEventId);

        // Load borda settings
        $this->bordaSettings = $event->borda_settings ?? [];

        // Load completeness data
        $service = app(DecisionSupportService::class);
        $this->completenessData = $service->getEvaluationCompleteness($this->selectedEventId);

        // Load Borda matrix data for display
        $this->loadBordaMatrix();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                if (!$this->selectedEventId) {
                    return \App\Models\BordaResult::query()->whereNull('id');
                }

                return \App\Models\BordaResult::query()
                    ->where('event_id', $this->selectedEventId)
                    ->with(['alternative', 'event']);
            })
            ->columns([
                TextColumn::make('alternative.name')
                    ->label('Alternative')
                    ->sortable(),

                TextColumn::make('total_borda_points')
                    ->label('Borda Points')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),

                TextColumn::make('final_rank')
                    ->label('Final Rank')
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state == 1 => 'success',
                        $state <= 3 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
            ])
            ->defaultSort('final_rank', 'asc');
    }

    protected function loadBordaMatrix(): void
    {
        if (!$this->selectedEventId) {
            $this->bordaMatrix = null;
            return;
        }

        $wpResults = \App\Models\WpResult::where('event_id', $this->selectedEventId)
            ->with('alternative')
            ->get();

        if ($wpResults->isEmpty()) {
            $this->bordaMatrix = null;
            return;
        }

        $bordaResults = \App\Models\BordaResult::where('event_id', $this->selectedEventId)
            ->with('alternative')
            ->orderBy('final_rank')
            ->get()
            ->keyBy('alternative_id');

        // Get total alternatives for max rank
        $totalAlternatives = $wpResults->pluck('alternative_id')->unique()->count();

        // Build matrix: alternative_id => [rank => sum of v_vectors]
        $matrix = [];
        foreach ($wpResults as $wp) {
            $altId = $wp->alternative_id;
            $rank = $wp->individual_rank;

            if (!isset($matrix[$altId])) {
                $matrix[$altId] = [
                    'alternative_code' => $wp->alternative->code,
                    'alternative_name' => $wp->alternative->name,
                    'ranks' => array_fill(1, $totalAlternatives, 0), // Initialize all ranks with 0
                ];
            }

            // Sum v_vectors for this alternative at this rank
            $matrix[$altId]['ranks'][$rank] += $wp->v_vector;
        }

        // Add Borda results to matrix
        foreach ($matrix as $altId => &$data) {
            if (isset($bordaResults[$altId])) {
                $data['borda_points'] = $bordaResults[$altId]->total_borda_points;
                $data['final_rank'] = $bordaResults[$altId]->final_rank;

                // Calculate Borda Value (normalized)
                $totalBordaPoints = $bordaResults->sum('total_borda_points');
                $data['borda_value'] = $totalBordaPoints > 0
                    ? $bordaResults[$altId]->total_borda_points / $totalBordaPoints
                    : 0;
            } else {
                $data['borda_points'] = 0;
                $data['borda_value'] = 0;
                $data['final_rank'] = 999;
            }
        }

        // Sort by final rank
        uasort($matrix, fn($a, $b) => $a['final_rank'] <=> $b['final_rank']);

        $this->bordaMatrix
            = [
                'data' => $matrix,
                'max_rank' => $totalAlternatives,
                'total_borda_points' => $bordaResults->sum('total_borda_points'),
            ];
    }

    public function getAlternatives()
    {
        if (!$this->selectedEventId) {
            return collect();
        }

        return Alternative::where('event_id', $this->selectedEventId)
            ->orderBy('code')
            ->get();
    }

    public function getCriteria()
    {
        if (!$this->selectedEventId) {
            return collect();
        }

        return Criterion::where('event_id', $this->selectedEventId)
            ->orderBy('name')
            ->get();
    }

    public function getMatrixData()
    {
        if (!$this->selectedEventId || !$this->userId) {
            return [];
        }

        // Fetch stored WP results from database
        $wpResults = WpResult::where('event_id', $this->selectedEventId)
            ->where('user_id', $this->userId)
            ->with('alternative')
            ->get();

        if ($wpResults->isEmpty()) {
            return [];
        }

        $alternatives = $this->getAlternatives();
        $criteria = $this->getCriteria();

        if ($alternatives->isEmpty() || $criteria->isEmpty()) {
            return [];
        }

        // Get all evaluations for this event and user (for displaying criterion scores)
        $evaluations = Evaluation::where('event_id', $this->selectedEventId)
            ->where('user_id', $this->userId)
            ->get()
            ->keyBy(function ($evaluation) {
                return "{$evaluation->alternative_id}:{$evaluation->criterion_id}";
            });

        // Calculate weight sum for power-by-weight display
        $weightSum = $criteria->sum('weight');

        if ($weightSum <= 0) {
            return [];
        }

        $matrix = [];

        // Build matrix using stored WP results
        foreach ($wpResults as $wpResult) {
            $altId = $wpResult->alternative_id;

            $matrix[$altId] = [
                'criteria' => [],
                's_vector' => $wpResult->s_vector,
                'v_vector' => $wpResult->v_vector,
                'rank' => $wpResult->individual_rank,
            ];

            // Calculate power-by-weight values for display (not used in actual calculation)
            foreach ($criteria as $criterion) {
                $critId = $criterion->id;
                $key = "{$altId}:{$critId}";

                $evaluation = $evaluations->get($key);

                if (!$evaluation) {
                    $matrix[$altId]['criteria'][$critId] = null;
                    continue;
                }

                $score = (float) $evaluation->score_value;
                $weight = (float) $criterion->weight;
                $normalizedWeight = $weight / $weightSum;

                // Determine power based on attribute type
                $power = $criterion->attribute_type === 'benefit'
                    ? abs($normalizedWeight)
                    : -abs($normalizedWeight);

                // Calculate power-by-weight value: score^power (for display only)
                $powerByWeight = pow($score, $power);

                $matrix[$altId]['criteria'][$critId] = $powerByWeight;
            }
        }

        return $matrix;
    }

    public function getTotalSVector()
    {
        if (!$this->selectedEventId || !$this->userId) {
            return 0.0;
        }

        // Sum S-vectors from stored WP results
        return WpResult::where('event_id', $this->selectedEventId)
            ->where('user_id', $this->userId)
            ->sum('s_vector');
    }

    public static function canAccess(): bool
    {
        return Auth::check();
    }
}
