<?php

namespace App\Filament\Resources\WpResults\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class WpResultForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'id')
                    ->required(),
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                Select::make('alternative_id')
                    ->relationship('alternative', 'name')
                    ->required(),
                TextInput::make('s_vector')
                    ->required()
                    ->numeric(),
                TextInput::make('v_vector')
                    ->required()
                    ->numeric(),
                TextInput::make('individual_rank')
                    ->required()
                    ->numeric(),
            ]);
    }
}
