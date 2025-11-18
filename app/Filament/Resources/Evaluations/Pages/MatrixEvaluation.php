<?php

declare(strict_types=1);

namespace App\Filament\Resources\Evaluations\Pages;

use App\Filament\Resources\Evaluations\EvaluationResource;
use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ViewField;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MatrixEvaluation extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $resource = EvaluationResource::class;

    protected string $view = 'filament.resources.evaluations.pages.matrix-evaluation';

    public ?array $data = [];

    public array $scores = [];

    public function mount(): void
    {
        $eventId = request()->query('event');

        $data = ['event_id' => $eventId, 'scores' => []];

        // Prefill existing evaluations if event is selected
        if ($eventId) {
            $evaluations = Evaluation::where('event_id', $eventId)
                ->where('user_id', auth()->id())
                ->get();

            foreach ($evaluations as $evaluation) {
                $this->scores[$evaluation->alternative_id][$evaluation->criterion_id] = $evaluation->score_value;
            }
        }

        $this->form->fill($data);
    }

    public function form($form)
    {
        return $form
            ->schema([
                Select::make('event_id')
                    ->label('Select Event')
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
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, callable $set) {
                        // Clear scores when event changes
                        $this->scores = [];

                        // Reload existing evaluations for new event
                        if ($state) {
                            $evaluations = Evaluation::where('event_id', $state)
                                ->where('user_id', auth()->id())
                                ->get();

                            foreach ($evaluations as $evaluation) {
                                $this->scores[$evaluation->alternative_id][$evaluation->criterion_id] = $evaluation->score_value;
                            }
                        }
                    }),


            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $eventId = $data['event_id'];
        $scores = $this->scores;

        // Log the raw scores data
        Log::info('Matrix Evaluation - Raw scores data:', [
            'event_id' => $eventId,
            'scores' => $scores,
            'scores_structure' => array_map(function ($alt) {
                return array_map(function ($val) {
                    return [
                        'value' => $val,
                        'type' => gettype($val),
                        'is_null' => is_null($val),
                        'is_empty_string' => $val === '',
                        'trimmed' => trim((string) $val),
                    ];
                }, $alt);
            }, $scores),
        ]);

        // Validate that all scores are filled
        $alternatives = Alternative::where('event_id', $eventId)->get();
        $criteria = Criterion::where('event_id', $eventId)->get();

        Log::info('Matrix Evaluation - Expected alternatives and criteria:', [
            'alternatives_count' => $alternatives->count(),
            'criteria_count' => $criteria->count(),
            'alternative_ids' => $alternatives->pluck('id')->toArray(),
            'criterion_ids' => $criteria->pluck('id')->toArray(),
        ]);

        if ($alternatives->isEmpty() || $criteria->isEmpty()) {
            Notification::make()
                ->title('Cannot Save Evaluations')
                ->danger()
                ->body('The selected event must have at least one alternative and one criterion.')
                ->send();
            return;
        }

        // Check if all cells are filled
        $missingScores = [];
        foreach ($alternatives as $alternative) {
            foreach ($criteria as $criterion) {
                $score = $scores[$alternative->id][$criterion->id] ?? null;

                // Check if score is empty or null
                if ($score === null || $score === '' || trim((string) $score) === '') {
                    $missingScores[] = "{$alternative->code} - {$criterion->name}";
                    continue;
                }

                // Validate score range
                $scoreValue = (float) $score;
                if ($scoreValue < 1.00 || $scoreValue > 5.00) {
                    Notification::make()
                        ->title('Invalid Score')
                        ->danger()
                        ->body("Score for {$alternative->code} - {$criterion->name} must be between 1.00 and 5.00.")
                        ->send();
                    return;
                }
            }
        }

        if (!empty($missingScores)) {
            Notification::make()
                ->title('Incomplete Evaluation')
                ->warning()
                ->body('Please fill in scores for: ' . implode(', ', array_slice($missingScores, 0, 3)) . (count($missingScores) > 3 ? ' and ' . (count($missingScores) - 3) . ' more...' : ''))
                ->send();
            return;
        }

        DB::transaction(function () use ($eventId, $scores) {
            // Delete existing evaluations for this user and event
            Evaluation::where('event_id', $eventId)
                ->where('user_id', auth()->id())
                ->delete();

            // Prepare bulk insert data
            $insertData = [];
            foreach ($scores as $alternativeId => $criteriaScores) {
                foreach ($criteriaScores as $criterionId => $score) {
                    $insertData[] = [
                        'event_id' => $eventId,
                        'user_id' => auth()->id(),
                        'alternative_id' => $alternativeId,
                        'criterion_id' => $criterionId,
                        'score_value' => $score,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }

            // Bulk insert new evaluations
            if (!empty($insertData)) {
                Evaluation::insert($insertData);
            }
        });

        Notification::make()
            ->title('Evaluations Saved Successfully')
            ->success()
            ->body('All scores have been saved for this event.')
            ->send();

        $this->redirect(EvaluationResource::getUrl('index'));
    }

    public function getTitle(): string
    {
        return 'Evaluate Alternatives';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save Evaluations')
                ->submit('save')
                ->requiresConfirmation()
                ->modalHeading('Save All Evaluations')
                ->modalDescription('This will replace all your previous evaluations for this event. Are you sure?')
                ->modalSubmitActionLabel('Yes, Save All'),
            Action::make('cancel')
                ->label('Cancel')
                ->color('gray')
                ->url(EvaluationResource::getUrl('index')),
        ];
    }

    public function saveAction(): Action
    {
        return Action::make('save')
            ->label('Save Evaluations')
            ->submit('save')
            ->requiresConfirmation()
            ->modalHeading('Save All Evaluations')
            ->modalDescription('This will replace all your previous evaluations for this event. Are you sure?')
            ->modalSubmitActionLabel('Yes, Save All');
    }

    public function cancelAction(): Action
    {
        return Action::make('cancel')
            ->label('Cancel')
            ->color('gray')
            ->url(EvaluationResource::getUrl('index'));
    }
}
