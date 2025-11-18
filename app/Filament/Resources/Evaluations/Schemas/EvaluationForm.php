<?php

namespace App\Filament\Resources\Evaluations\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EvaluationForm
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
                Select::make('criterion_id')
                    ->relationship('criterion', 'name')
                    ->required(),
                TextInput::make('score_value')
                    ->required()
                    ->numeric(),
            ]);
    }
}
