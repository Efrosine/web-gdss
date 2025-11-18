<?php

declare(strict_types=1);

namespace App\Filament\Resources\BordaResults;

use App\Filament\Resources\BordaResults\Pages\ListBordaResults;
use App\Filament\Resources\BordaResults\Pages\ViewBordaResult;
use App\Filament\Resources\BordaResults\Schemas\BordaResultInfolist;
use App\Filament\Resources\BordaResults\Tables\BordaResultsTable;
use App\Models\BordaResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class BordaResultResource extends Resource
{
    protected static ?string $model = BordaResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Results & Rankings';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // If decision maker, only show results for assigned events
        if ($user->role === 'decision_maker') {
            $query->whereHas('event', function (Builder $q) use ($user) {
                $q->whereHas('users', function (Builder $q2) use ($user) {
                    $q2->where('user_id', $user->id);
                });
            });
        }

        return $query;
    }

    public static function infolist(Schema $schema): Schema
    {
        return BordaResultInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BordaResultsTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBordaResults::route('/'),
            'view' => ViewBordaResult::route('/{record}'),
        ];
    }
}
