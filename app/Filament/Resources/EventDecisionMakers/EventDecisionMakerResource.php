<?php

declare(strict_types=1);

namespace App\Filament\Resources\EventDecisionMakers;

use App\Filament\Resources\EventDecisionMakers\Pages\ManageEventDecisionMakers;
use App\Models\Event;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class EventDecisionMakerResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Management';

    protected static ?string $navigationLabel = 'DM Assignments';

    protected static ?string $modelLabel = 'Decision Maker Assignment';

    protected static ?string $pluralModelLabel = 'Decision Maker Assignments';

    protected static ?int $navigationSort = 2;

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->label('Event')
                    ->relationship('event', 'event_name')
                    ->required()
                    ->disabled(fn($context) => $context === 'edit')
                    ->searchable()
                    ->preload(),

                Repeater::make('decisionMakers')
                    ->label('Assigned Decision Makers')
                    ->relationship('decisionMakers')
                    ->schema([
                        Select::make('id')
                            ->label('Decision Maker')
                            ->options(fn() => User::where('role', 'decision_maker')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->distinct()
                            ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                        Toggle::make('is_leader')
                            ->label('Event Leader')
                            ->default(false)
                            ->helperText('Only one leader per event is recommended')
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    ->addActionLabel('Add Decision Maker')
                    ->itemLabel(
                        fn(array $state): ?string =>
                        User::find($state['id'])?->name ?? null
                    )
                    ->reorderable(false)
                    ->collapsible()
                    ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                        return [
                            'id' => $data['id'],
                            'is_leader' => $data['pivot']['is_leader'] ?? false,
                        ];
                    })
                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                        return [
                            'is_leader' => $data['is_leader'] ?? false,
                            'assigned_at' => now(),
                        ];
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('event_name')
                    ->label('Event')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                TextColumn::make('event_date')
                    ->label('Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('decision_makers_count')
                    ->label('Total DMs')
                    ->counts('decisionMakers')
                    ->badge()
                    ->color('info'),

                TextColumn::make('leaders.name')
                    ->label('Event Leader')
                    ->badge()
                    ->color('success')
                    ->default('No Leader Assigned')
                    ->limit(30),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('manage')
                    ->label('Manage')
                    ->icon('heroicon-o-pencil-square')
                    ->modalHeading(fn(Event $record) => 'Manage DMs: ' . $record->event_name)
                    ->modalWidth('3xl')
                    ->fillForm(fn(Event $record): array => [
                        'event_id' => $record->id,
                        'decisionMakers' => $record->decisionMakers->map(fn($dm) => [
                            'id' => $dm->id,
                            'is_leader' => $dm->pivot->is_leader,
                        ])->toArray(),
                    ])
                    ->form([
                        Repeater::make('decisionMakers')
                            ->label('Assigned Decision Makers')
                            ->schema([
                                Select::make('id')
                                    ->label('Decision Maker')
                                    ->options(fn() => User::where('role', 'decision_maker')->pluck('name', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                Toggle::make('is_leader')
                                    ->label('Event Leader')
                                    ->default(false)
                                    ->inline(false),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->addActionLabel('Add Decision Maker')
                            ->itemLabel(
                                fn(array $state): ?string =>
                                User::find($state['id'])?->name ?? null
                            )
                            ->reorderable(false)
                            ->collapsible(),
                    ])
                    ->action(function (Event $record, array $data): void {
                        // Sync decision makers with pivot data
                        $syncData = [];
                        foreach ($data['decisionMakers'] as $dm) {
                            $syncData[$dm['id']] = [
                                'is_leader' => $dm['is_leader'] ?? false,
                                'assigned_at' => now(),
                            ];
                        }
                        $record->decisionMakers()->sync($syncData);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('event_date', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageEventDecisionMakers::route('/'),
        ];
    }
}
