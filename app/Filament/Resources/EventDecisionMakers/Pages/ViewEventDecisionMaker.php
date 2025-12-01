<?php

namespace App\Filament\Resources\EventDecisionMakers\Pages;

use App\Filament\Resources\EventDecisionMakers\EventDecisionMakerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewEventDecisionMaker extends ViewRecord
{
    protected static string $resource = EventDecisionMakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
