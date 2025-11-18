<?php

namespace App\Filament\Resources\Evaluations\Pages;

use App\Filament\Resources\Evaluations\EvaluationResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageEvaluations extends ManageRecords
{
    protected static string $resource = EvaluationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('matrix')
                ->label('Evaluate Alternatives (Matrix)')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->url(fn() => EvaluationResource::getUrl('matrix')),
            CreateAction::make()
                ->label('Add Single Evaluation'),
        ];
    }
}
