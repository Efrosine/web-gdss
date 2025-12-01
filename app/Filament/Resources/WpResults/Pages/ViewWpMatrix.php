<?php

declare(strict_types=1);

namespace App\Filament\Resources\WpResults\Pages;

use App\Filament\Resources\WpResults\WpResultResource;
use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ViewWpMatrix extends ListRecords
{
    protected static string $resource = WpResultResource::class;

    public ?int $eventId = null;

    public ?int $userId = null;

    public function mount(): void
    {
        parent::mount();
        $this->eventId = request()->query('event');
        $this->userId = request()->query('user');
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Select Event and Decision Maker')
                    ->description('Choose an event and decision maker to view WP results in matrix format')
                    ->schema([
                        Select::make('eventId')
                            ->label('Event')
                            ->options(function () {
                                $user = auth()->user();
                                $query = \App\Models\Event::query();

                                if ($user->role === 'decision_maker') {
                                    $query->whereHas('users', function (Builder $q) use ($user) {
                                        $q->where('user_id', $user->id);
                                    });
                                }

                                return $query->pluck('event_name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Select an event to view WP results')
                            ->afterStateUpdated(function ($state) {
                                $this->eventId = $state;
                                $this->userId = null; // Reset user when event changes
                            }),
                        Select::make('userId')
                            ->label('Decision Maker')
                            ->options(function () {
                                if (!$this->eventId) {
                                    return [];
                                }

                                $user = auth()->user();

                                // Get users who have WP results for this event
                                $query = \App\Models\User::query()
                                    ->whereHas('wpResults', function (Builder $q) {
                                    $q->where('event_id', $this->eventId);
                                });

                                // If current user is decision maker, only show themselves
                                // if ($user->role === 'decision_maker') {
                                //     $query->where('id', $user->id);
                                // }
                    
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->placeholder('Select a decision maker')
                            ->disabled(fn() => !$this->eventId)
                            ->afterStateUpdated(fn($state) => $this->userId = $state),
                    ]),
                Section::make('WP Results Matrix')
                    ->description(fn() => $this->userId
                        ? 'Viewing WP results from: ' . \App\Models\User::find($this->userId)?->name
                        : 'Select a decision maker to view their WP results')
                    ->schema([
                        \Filament\Schemas\Components\View::make('filament.resources.wp-results.components.view-wp-matrix')
                            ->viewData(fn() => [
                                'alternatives' => $this->getAlternatives(),
                                'criteria' => $this->getCriteria(),
                                'matrixData' => $this->getMatrixData(),
                                'totalSVector' => $this->getTotalSVector(),
                            ]),
                    ])
                    ->visible(fn() => $this->eventId !== null && $this->userId !== null),
            ]);
    }

    public function getAlternatives()
    {
        if (!$this->eventId) {
            return collect();
        }

        return Alternative::where('event_id', $this->eventId)
            ->orderBy('code')
            ->get();
    }

    public function getCriteria()
    {
        if (!$this->eventId) {
            return collect();
        }

        return Criterion::where('event_id', $this->eventId)
            ->orderBy('name')
            ->get();
    }

    public function getMatrixData()
    {
        if (!$this->eventId || !$this->userId) {
            return [];
        }

        $alternatives = $this->getAlternatives();
        $criteria = $this->getCriteria();

        if ($alternatives->isEmpty() || $criteria->isEmpty()) {
            return [];
        }

        // Get all evaluations for this event and user
        $evaluations = Evaluation::where('event_id', $this->eventId)
            ->where('user_id', $this->userId)
            ->get()
            ->keyBy(function ($evaluation) {
                return "{$evaluation->alternative_id}:{$evaluation->criterion_id}";
            });

        // Calculate weight sum for normalization
        $weightSum = $criteria->sum('weight');

        if ($weightSum <= 0) {
            return [];
        }

        $matrix = [];

        foreach ($alternatives as $alternative) {
            $altId = $alternative->id;
            $matrix[$altId] = [
                'criteria' => [],
                's_vector' => 0.0,
                'v_vector' => 0.0,
            ];

            $logS = 0.0;

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

                // Calculate power-by-weight value: score^power
                $powerByWeight = pow($score, $power);

                // For log calculation (used in s_vector)
                $logS += $power * log($score);

                $matrix[$altId]['criteria'][$critId] = $powerByWeight;
            }

            // Calculate S-vector for this alternative
            $matrix[$altId]['s_vector'] = exp($logS);
        }

        // Calculate total S-vector sum
        $totalSVector = array_sum(array_column($matrix, 's_vector'));

        // Calculate V-vector (normalized S-vector)
        if ($totalSVector > 0) {
            foreach ($matrix as $altId => &$altData) {
                $altData['v_vector'] = $altData['s_vector'] / $totalSVector;
            }
        }

        return $matrix;
    }

    public function getTotalSVector()
    {
        $matrixData = $this->getMatrixData();

        if (empty($matrixData)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($matrixData as $altData) {
            $total += $altData['s_vector'];
        }

        return $total;
    }

    public function getTitle(): string
    {
        return 'WP Results Matrix';
    }
}
