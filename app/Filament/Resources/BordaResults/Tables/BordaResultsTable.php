<?php

declare(strict_types=1);

namespace App\Filament\Resources\BordaResults\Tables;

use App\Models\BordaResult;
use App\Models\Event;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class BordaResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('final_rank')
                    ->label('Rank')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state === 1 => 'success',
                        $state === 2 => 'info',
                        $state === 3 => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('alternative.name')
                    ->label('Alternative')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_borda_points')
                    ->label('Total Borda Points')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
            ])
            ->defaultGroup(
                Group::make('event.event_name')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn(BordaResult $record): string =>
                        '**' . ($record->event?->event_name ?? 'Unknown Event') . '** (' .
                        ($record->event?->created_at->translatedFormat('d F Y') ??
                            $record->created_at->translatedFormat('d F Y')) . ')'
                    )
            )
            ->groupingSettingsHidden()
            ->filters([
                SelectFilter::make('event_id')
                    ->label('Event')
                    ->options(Event::pluck('event_name', 'id'))
                    ->searchable(),
            ])
            ->defaultSort('final_rank', 'asc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
