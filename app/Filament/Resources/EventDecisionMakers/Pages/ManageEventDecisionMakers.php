<?php

namespace App\Filament\Resources\EventDecisionMakers\Pages;

use App\Filament\Resources\EventDecisionMakers\EventDecisionMakerResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEventDecisionMakers extends ManageRecords
{
    protected static string $resource = EventDecisionMakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
