<?php

namespace App\Filament\Resources\Evaluations\Pages;

use App\Filament\Resources\Evaluations\EvaluationResource;
use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class ManageEvaluations extends ListRecords
{
    protected static string $resource = EvaluationResource::class;

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
                Section::make('Select Event')
                    ->description('Choose an event to view all evaluations in matrix format')
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
                            ->placeholder('Select an event to view evaluations')
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

                                // Get users who have evaluated this event
                                $query = \App\Models\User::query()
                                    ->whereHas('evaluations', function (Builder $q) {
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
                Section::make('Evaluation Matrix')
                    ->description(fn() => $this->userId
                        ? 'Viewing evaluations from: ' . \App\Models\User::find($this->userId)?->name
                        : 'Select a decision maker to view their evaluations')
                    ->schema([
                        \Filament\Schemas\Components\View::make('filament.resources.evaluations.components.view-matrix')
                            ->viewData(fn() => [
                                'alternatives' => $this->getAlternatives(),
                                'criteria' => $this->getCriteria(),
                                'matrixData' => $this->getMatrixData(),
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

        return Alternative::where('event_id', $this->eventId)->orderBy('code')->get();
    }

    public function getCriteria()
    {
        if (!$this->eventId) {
            return collect();
        }

        return Criterion::where('event_id', $this->eventId)->orderBy('name')->get();
    }

    public function getMatrixData()
    {
        if (!$this->eventId || !$this->userId) {
            return [];
        }

        $evaluations = Evaluation::where('event_id', $this->eventId)
            ->where('user_id', $this->userId)
            ->with(['user', 'alternative', 'criterion'])
            ->get();

        $matrix = [];
        foreach ($evaluations as $evaluation) {
            $altId = $evaluation->alternative_id;
            $critId = $evaluation->criterion_id;

            if (!isset($matrix[$altId])) {
                $matrix[$altId] = [];
            }
            if (!isset($matrix[$altId][$critId])) {
                $matrix[$altId][$critId] = [];
            }

            $matrix[$altId][$critId][] = [
                'user' => $evaluation->user->name,
                'score' => $evaluation->score_value,
            ];
        }

        return $matrix;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matrix')
                ->label('Evaluate Alternatives (Matrix)')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn() => EvaluationResource::getUrl('matrix', ['event' => $this->eventId])),
        ];
    }

    public function getTitle(): string
    {
        return 'View Evaluations';
    }
}
