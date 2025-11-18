<?php

declare(strict_types=1);

namespace App\Filament\Resources\WpResults;

use App\Filament\Resources\WpResults\Pages\ListWpResults;
use App\Filament\Resources\WpResults\Pages\ViewWpResult;
use App\Filament\Resources\WpResults\Schemas\WpResultInfolist;
use App\Filament\Resources\WpResults\Tables\WpResultsTable;
use App\Models\WpResult;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WpResultResource extends Resource
{
    protected static ?string $model = WpResult::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Results & Rankings';

    protected static ?int $navigationSort = 1;

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
        return WpResultInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WpResultsTable::configure($table);
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
            'index' => ListWpResults::route('/'),
            'view' => ViewWpResult::route('/{record}'),
        ];
    }
}
