<?php

namespace App\Filament\Resources\EventDecisionMakers\Pages;

use App\Filament\Resources\EventDecisionMakers\EventDecisionMakerResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditEventDecisionMaker extends EditRecord
{
    protected static string $resource = EventDecisionMakerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
