<?php

namespace App\Filament\Resources\BordaResults\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BordaResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'id')
                    ->required(),
                Select::make('alternative_id')
                    ->relationship('alternative', 'name')
                    ->required(),
                TextInput::make('total_borda_points')
                    ->required()
                    ->numeric(),
                TextInput::make('final_rank')
                    ->required()
                    ->numeric(),
            ]);
    }
}
