<?php

namespace App\Filament\Resources\BordaResults\Pages;

use App\Filament\Resources\BordaResults\BordaResultResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBordaResults extends ListRecords
{
    protected static string $resource = BordaResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
