<?php

namespace App\Filament\Resources\WpResults\Pages;

use App\Filament\Resources\WpResults\WpResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWpResults extends ListRecords
{
    protected static string $resource = WpResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
