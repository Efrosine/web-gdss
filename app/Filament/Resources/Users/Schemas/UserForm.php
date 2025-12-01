<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->maxLength(255),

                Select::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'decision_maker' => 'Decision Maker'
                    ])
                    ->default('decision_maker')
                    ->required(),

                TextInput::make('position')
                    ->maxLength(255)
                    ->nullable()
                    ->helperText('Job title or position (e.g., Professor, Lecturer)'),

                TextInput::make('password')
                    ->password()
                    ->required(fn(string $context): bool => $context === 'create')
                    ->dehydrateStateUsing(fn($state) => !empty($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => !empty($state))
                    ->revealable()
                    ->maxLength(255)
                    ->helperText('Leave empty to keep current password when editing'),
            ]);
    }
}
