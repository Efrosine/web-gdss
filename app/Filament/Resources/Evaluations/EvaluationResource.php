<?php

declare(strict_types=1);

namespace App\Filament\Resources\Evaluations;

use App\Filament\Resources\Evaluations\Pages;
use App\Filament\Resources\Evaluations\Pages\ManageEvaluations;
use App\Models\Alternative;
use App\Models\Criterion;
use App\Models\Evaluation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;

class EvaluationResource extends Resource
{
    protected static ?string $model = Evaluation::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|UnitEnum|null $navigationGroup = 'Data Input';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // If decision maker, only show evaluations for assigned events
        if ($user->role === 'decision_maker') {
            $query->whereHas('event', function (Builder $q) use ($user) {
                $q->whereHas('users', function (Builder $q2) use ($user) {
                    $q2->where('user_id', $user->id);
                });
            });
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship(
                        'event',
                        'event_name',
                        modifyQueryUsing: function (Builder $query) {
                            $user = auth()->user();
                            // Decision makers only see their assigned events
                            if ($user->role === 'decision_maker') {
                                $query->whereHas('users', function (Builder $q) use ($user) {
                                    $q->where('user_id', $user->id);
                                });
                            }
                        }
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn(callable $set) => $set('alternative_id', null) + $set('criterion_id', null)),
                Select::make('alternative_id')
                    ->label('Alternative')
                    ->options(fn(Get $get) => Alternative::query()
                        ->where('event_id', $get('event_id'))
                        ->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->disabled(fn(Get $get) => !$get('event_id')),
                Select::make('criterion_id')
                    ->label('Criterion')
                    ->options(fn(Get $get) => Criterion::query()
                        ->where('event_id', $get('event_id'))
                        ->pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->disabled(fn(Get $get) => !$get('event_id')),
                TextInput::make('score_value')
                    ->label('Score (1.00 - 5.00)')
                    ->required()
                    ->numeric()
                    ->minValue(1.00)
                    ->maxValue(5.00)
                    ->step(0.01)
                    ->default(3.00)
                    ->helperText('Enter a score between 1.00 and 5.00'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Evaluated By')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alternative.name')
                    ->label('Alternative')
                    ->searchable(),
                TextColumn::make('criterion.name')
                    ->label('Criterion')
                    ->searchable(),
                TextColumn::make('score_value')
                    ->label('Score')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup(
                Group::make('event.event_name')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn(Evaluation $record): string =>
                        '**' . ($record->event?->event_name ?? 'Unknown Event') . '** (' .
                        ($record->event?->created_at->translatedFormat('d F Y') ??
                            $record->created_at->translatedFormat('d F Y')) . ')'
                    )
            )
            ->groupingSettingsHidden()
            ->filters([
                SelectFilter::make('event')
                    ->relationship('event', 'event_name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEvaluations::route('/'),
            'matrix' => Pages\MatrixEvaluation::route('/matrix'),
        ];
    }
}
