<?php

declare(strict_types=1);

namespace App\Filament\Resources\Criteria;

use App\Filament\Resources\Criteria\Pages\ManageCriteria;
use App\Models\Criterion;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class CriterionResource extends Resource
{
    protected static ?string $model = Criterion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Data Input';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'event_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('weight')
                    ->required()
                    ->numeric()
                    ->minValue(0.01)
                    ->step(0.01)
                    ->default(1.00)
                    ->helperText('Weights will be automatically normalized (each weight divided by sum of all weights)'),
                Select::make('attribute_type')
                    ->options([
                        'benefit' => 'Benefit (Higher is better)',
                        'cost' => 'Cost (Lower is better)'
                    ])
                    ->required()
                    ->default('benefit'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::Bold),
                TextColumn::make('weight')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('attribute_type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'benefit' => 'success',
                        'cost' => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup(
                Group::make('event.event_name')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn(Criterion $record): string =>
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
                SelectFilter::make('attribute_type')
                    ->options([
                        'benefit' => 'Benefit',
                        'cost' => 'Cost',
                    ]),
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
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCriteria::route('/'),
        ];
    }
}
