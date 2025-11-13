<?php

namespace App\Filament\Resources\MarkupRules\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarkupRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                
                Select::make('type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed_amount' => 'Fixed Amount',
                        'tiered' => 'Tiered',
                    ])
                    ->required()
                    ->live(),
                
                TextInput::make('markup_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->visible(fn ($get) => $get('type') === 'percentage'),
                
                TextInput::make('fixed_amount')
                    ->numeric()
                    ->prefix('KES')
                    ->visible(fn ($get) => $get('type') === 'fixed_amount'),
                
            Repeater::make('tiers')
                    ->schema([
                        TextInput::make('min')
                            ->numeric()
                            ->required(),
                        TextInput::make('max')
                            ->numeric()
                            ->required(),
                        TextInput::make('markup_percentage')
                            ->numeric()
                            ->suffix('%')
                            ->required(),
                    ])
                    ->visible(fn ($get) => $get('type') === 'tiered'),
                
                TextInput::make('priority')
                    ->numeric()
                    ->default(0)
                    ->helperText('Higher priority rules are evaluated first'),
                
                Toggle::make('is_active')
                    ->default(true),
                
                DatePicker::make('valid_from'),
                DatePicker::make('valid_until'),
            ]);
    }
}
