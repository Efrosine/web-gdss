<?php

namespace App\Filament\Resources\Criteria\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CriterionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'id')
                    ->required(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('weight')
                    ->required()
                    ->numeric(),
                Select::make('attribute_type')
                    ->options(['benefit' => 'Benefit', 'cost' => 'Cost'])
                    ->required(),
            ]);
    }
}
