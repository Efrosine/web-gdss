<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Models\BordaResult;
use App\Models\Event;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class BordaRankingsWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                BordaResult::query()
                    ->with(['event', 'alternative'])
                    ->orderBy('final_rank')
                    ->limit(10)
            )
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
                    ->label('Borda Points')
                    ->sortable()
                    ->numeric(decimalPlaces: 0),
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
            ->defaultSort('final_rank', 'asc');
    }
}
