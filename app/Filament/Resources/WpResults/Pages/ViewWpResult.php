<?php

namespace App\Filament\Resources\WpResults\Pages;

use App\Filament\Resources\WpResults\WpResultResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWpResult extends ViewRecord
{
    protected static string $resource = WpResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
