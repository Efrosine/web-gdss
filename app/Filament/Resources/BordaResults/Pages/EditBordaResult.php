<?php

namespace App\Filament\Resources\BordaResults\Pages;

use App\Filament\Resources\BordaResults\BordaResultResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBordaResult extends EditRecord
{
    protected static string $resource = BordaResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
