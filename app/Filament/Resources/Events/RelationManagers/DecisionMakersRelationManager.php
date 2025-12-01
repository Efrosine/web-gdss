<?php

namespace App\Filament\Resources\Events\RelationManagers;

use App\Filament\Resources\Events\EventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;

class DecisionMakersRelationManager extends RelationManager
{
    protected static string $relationship = 'decisionMakers';

    protected static ?string $relatedResource = EventResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
