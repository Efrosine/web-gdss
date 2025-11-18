<?php

declare(strict_types=1);

namespace App\Filament\Resources\WpResults\Tables;

use App\Models\Event;
use App\Models\User;
use App\Models\WpResult;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class WpResultsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Decision Maker')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('alternative.name')
                    ->label('Alternative')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('s_vector')
                    ->label('S-Vector')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('v_vector')
                    ->label('V-Vector')
                    ->numeric(decimalPlaces: 4)
                    ->sortable(),
                TextColumn::make('individual_rank')
                    ->label('Rank')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state === 1 => 'success',
                        $state === 2 => 'info',
                        $state === 3 => 'warning',
                        default => 'gray',
                    }),
            ])
            ->defaultGroup(
                Group::make('event.event_name')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn(WpResult $record): string =>
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
                SelectFilter::make('user_id')
                    ->label('Decision Maker')
                    ->options(User::pluck('name', 'id'))
                    ->searchable(),
            ])
            ->defaultSort('individual_rank', 'asc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
