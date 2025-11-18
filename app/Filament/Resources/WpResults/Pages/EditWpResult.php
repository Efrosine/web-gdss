<?php

namespace App\Filament\Resources\WpResults\Pages;

use App\Filament\Resources\WpResults\WpResultResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWpResult extends EditRecord
{
    protected static string $resource = WpResultResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
