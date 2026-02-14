<?php

namespace App\Filament\Resources\Prescriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PrescriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('prescription_number')
                    ->required(),
                Select::make('physician_id')
                    ->relationship('physician', 'name')
                    ->required(),
                Select::make('patient_id')
                    ->relationship('patient', 'id')
                    ->required(),
                Textarea::make('diagnosis')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options([
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'processing' => 'Processing',
            'fulfilled' => 'Fulfilled',
            'cancelled' => 'Cancelled',
        ])
                    ->default('draft')
                    ->required(),
                TextInput::make('total_amount')
                    ->numeric()
                    ->default(null),
                Toggle::make('insurance_covered')
                    ->required(),
                DateTimePicker::make('prescribed_at')
                    ->required(),
                DateTimePicker::make('expires_at'),
                DateTimePicker::make('fulfilled_at'),
                Select::make('insurance_claim_id')
                    ->relationship('insuranceClaim', 'id')
                    ->default(null),
            ]);
    }
}
