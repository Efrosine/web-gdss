<?php

namespace App\Filament\Resources\BordaResults\Pages;

use App\Filament\Resources\BordaResults\BordaResultResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewBordaResult extends ViewRecord
{
    protected static string $resource = BordaResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
