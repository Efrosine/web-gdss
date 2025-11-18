<?php

namespace App\Filament\Resources\BordaResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class BordaResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event.id')
                    ->label('Event'),
                TextEntry::make('alternative.name')
                    ->label('Alternative'),
                TextEntry::make('total_borda_points')
                    ->numeric(),
                TextEntry::make('final_rank')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
