<?php

declare(strict_types=1);

namespace App\Filament\Resources\Alternatives;

use App\Filament\Resources\Alternatives\Pages\ManageAlternatives;
use App\Models\Alternative;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use BackedEnum;
use UnitEnum;

class AlternativeResource extends Resource
{
    protected static ?string $model = Alternative::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-user-group';

    protected static string|UnitEnum|null $navigationGroup = 'Data Input';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('event_id')
                    ->relationship('event', 'event_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                TextInput::make('code')
                    ->required()
                    ->placeholder('A1')
                    ->maxLength(10),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('nip')
                    ->label('NIP')
                    ->required()
                    ->numeric()
                    ->length(10)
                    ->placeholder('1234567890'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nip')
                    ->label('NIP')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultGroup(
                Group::make('event.event_name')
                    ->collapsible()
                    ->getTitleFromRecordUsing(
                        fn(Alternative $record): string =>
                        '**' . ($record->event?->event_name ?? 'Unknown Event') . '** (' .
                        ($record->event?->created_at->translatedFormat('d F Y') ??
                            $record->created_at->translatedFormat('d F Y')) . ')'
                    )
            )
            ->groupingSettingsHidden()
            ->filters([
                SelectFilter::make('event')
                    ->relationship('event', 'event_name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('code', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAlternatives::route('/'),
        ];
    }
}
