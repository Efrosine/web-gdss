<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Event;
use App\Models\User;
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

    protected static ?string $navigationLabel = 'Event Results';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.event-results';

    public ?int $selectedEventId = null;
    public ?array $bordaSettings = null;
    public ?array $completenessData = null;
    public bool $canManage = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('selectedEventId')
                    ->label('Select Event')
                    ->options(function () {
                        $user = Auth::user();
                        if ($user->isAdmin()) {
                            return Event::pluck('event_name', 'event_id');
                        }
                        // Decision makers see only assigned events
                        return $user->events()->pluck('event_name', 'event_id');
                    })
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(function ($state) {
                        $this->selectedEventId = $state;
                        $this->loadEventData();
                    })
                    ->required(),

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
                    ->numeric()
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

    public static function canAccess(): bool
    {
        return Auth::check();
    }
}
