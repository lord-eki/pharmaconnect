<?php

namespace App\Filament\Physician\Resources\Physician\Patients\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PatientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Personal Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),
                                    
                                TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),
                                    
                                DatePicker::make('date_of_birth')
                                    ->required()
                                    ->maxDate(now())
                                    ->native(false)
                                    ->displayFormat('d/m/Y'),
                                    
                                Select::make('gender')
                                    ->options([
                                        'male' => 'Male',
                                        'female' => 'Female',
                                    ])
                                    ->required(),
                            ]),
                    ]),                    
                Section::make('Contact Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(255)
                                    ->placeholder('+254 7XX XXX XXX'),
                                    
                                TextInput::make('email')
                                    ->email()
                                    ->maxLength(255),
                                    
                                Textarea::make('address')
                                    ->rows(2)
                                    ->columnSpanFull(),
                                    
                                TextInput::make('county')
                                    ->maxLength(255),
                                    
                                TextInput::make('city')
                                    ->maxLength(255),
                            ]),
                        ]),
                    
                Section::make('Emergency Contact')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('emergency_contact_name')
                                    ->label('Name')
                                    ->maxLength(255),
                                    
                                TextInput::make('emergency_contact_phone')
                                    ->label('Phone')
                                    ->tel()
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible(),
                    
                Section::make('Insurance Information')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('insurance_provider')
                                    ->label('Provider')
                                    ->maxLength(255),
                                    
                                TextInput::make('insurance_number')
                                    ->label('Policy/Member Number')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible(),
                    
                Section::make('Medical Information')
                    ->schema([
                        Textarea::make('allergies')
                            ->rows(3)
                            ->placeholder('List any known allergies (drugs, food, environmental, etc.)')
                            ->columnSpanFull(),
                            
                        Textarea::make('medical_conditions')
                            ->rows(3)
                            ->placeholder('List any chronic conditions, past surgeries, or relevant medical history')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
                    
                Section::make('Status')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Active Patient')
                            ->default(true)
                            ->helperText('Inactive patients will not appear in prescription creation'),
                    ])
                    ->collapsible(),
            ]);
    }
}
