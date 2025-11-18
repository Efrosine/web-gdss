<?php

namespace App\Filament\Resources\WpResults\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class WpResultInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('event.id')
                    ->label('Event'),
                TextEntry::make('user.name')
                    ->label('User'),
                TextEntry::make('alternative.name')
                    ->label('Alternative'),
                TextEntry::make('s_vector')
                    ->numeric(),
                TextEntry::make('v_vector')
                    ->numeric(),
                TextEntry::make('individual_rank')
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
