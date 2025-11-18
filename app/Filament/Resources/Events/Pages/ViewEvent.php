<?php

declare(strict_types=1);

namespace App\Filament\Resources\Events\Pages;

use App\Filament\Resources\Events\EventResource;
use App\Services\DecisionSupportService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewEvent extends ViewRecord
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('calculate')
                ->label('Calculate Rankings')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Calculate WP & Borda Rankings')
                ->modalDescription('This will recalculate Weighted Product and Borda aggregation results for this event. Any existing results will be replaced.')
                ->modalSubmitActionLabel('Calculate')
                ->action(function (DecisionSupportService $service) {
                    try {
                        $service->calculate($this->record->id);

                        Notification::make()
                            ->title('Rankings Calculated Successfully')
                            ->success()
                            ->body('WP and Borda results have been calculated and stored.')
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Calculation Failed')
                            ->danger()
                            ->body($e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
