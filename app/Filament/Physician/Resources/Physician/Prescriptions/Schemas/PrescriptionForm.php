<?php

namespace App\Filament\Physician\Resources\Physician\Prescriptions\Schemas;

use App\Models\Medicine;
use App\Models\Patient;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PrescriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Patient Information')
                    ->schema([
                        Select::make('patient_id')
                            ->label('Select Patient')
                            ->relationship(
                                name: 'patient',
                                titleAttribute: 'first_name',
                                modifyQueryUsing: fn (Builder $query) => $query->where('physician_id', Auth::id())
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} - {$record->patient_number}")
                            ->searchable(['first_name', 'last_name', 'patient_number', 'phone'])
                            ->preload()
                            ->required()
                            ->createOptionForm([
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
                                            ->maxDate(now()),
                                        Select::make('gender')
                                            ->options([
                                                'male' => 'Male',
                                                'female' => 'Female',
                                            ])
                                            ->required(),
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('county')
                                            ->maxLength(255),
                                        TextInput::make('city')
                                            ->maxLength(255),
                                        Textarea::make('address')
                                            ->columnSpanFull(),
                                        TextInput::make('insurance_number')
                                            ->maxLength(255),
                                        TextInput::make('insurance_provider')
                                            ->maxLength(255),
                                        Textarea::make('allergies')
                                            ->columnSpanFull()
                                            ->placeholder('List any known allergies'),
                                        Textarea::make('medical_conditions')
                                            ->columnSpanFull()
                                            ->placeholder('List any existing medical conditions'),
                                    ]),
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state) {
                                    $patient = Patient::find($state);
                                    if ($patient) {
                                        // Display patient info for reference
                                        $set('patient_allergies_display', $patient->allergies);
                                        $set('patient_conditions_display', $patient->medical_conditions);
                                    }
                                }
                            }),
                        
                        Placeholder::make('patient_allergies_display')
                            ->label('Patient Allergies')
                            ->content(fn ($state) => $state ?: 'No known allergies')
                            ->visible(fn (Get $get) => $get('patient_id')),
                        
                        Placeholder::make('patient_conditions_display')
                            ->label('Medical Conditions')
                            ->content(fn ($state) => $state ?: 'No known conditions')
                            ->visible(fn (Get $get) => $get('patient_id')),
                    ])
                    ->columns(1),

                Section::make('Prescription Details')
                    ->schema([
                        Textarea::make('diagnosis')
                            ->rows(3)
                            ->columnSpanFull(),
                        
                        Textarea::make('notes')
                            ->rows(2)
                            ->columnSpanFull()
                            ->placeholder('Additional notes or instructions'),
                        
                        Toggle::make('insurance_covered')
                            ->label('Insurance Coverage')
                            ->helperText('Does this prescription have insurance coverage?')
                            ->default(false),
                    ])
                    ->columns(1),

                Section::make('Medicines')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('medicine_id')
                                    ->label('Medicine')
                                    ->relationship('medicine', 'generic_name')
                                    ->getOptionLabelFromRecordUsing(function ($record) {
                                        $brandInfo = $record->brand_name ? " ({$record->brand_name})" : '';
                                        return "{$record->generic_name}{$brandInfo} - {$record->strength} - {$record->dosage_form}";
                                    })
                                    ->searchable(['generic_name', 'brand_name', 'active_ingredients'])
                                    ->preload()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        if ($state) {
                                            $medicine = Medicine::with(['supplierMedicines' => function($query) {
                                                $query->where('is_available', true)
                                                      ->orderBy('unit_price', 'asc');
                                            }])->find($state);
                                            
                                            if ($medicine && $medicine->supplierMedicines->isNotEmpty()) {
                                                $lowestPrice = $medicine->supplierMedicines->first()->unit_price;
                                                $set('unit_price', $lowestPrice);
                                                
                                                // Calculate total if quantity exists
                                                $quantity = $get('quantity');
                                                if ($quantity) {
                                                    $set('total_price', $lowestPrice * $quantity);
                                                }
                                            }
                                            
                                            // Set medicine info for display
                                            if ($medicine) {
                                                $set('medicine_info', [
                                                    'usage' => $medicine->usage_instructions,
                                                    'side_effects' => $medicine->side_effects,
                                                    'contraindications' => $medicine->contraindications,
                                                ]);
                                            }
                                        }
                                    })
                                    ->columnSpan(2),

                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                        $unitPrice = $get('unit_price');
                                        if ($state && $unitPrice) {
                                            $set('total_price', $state * $unitPrice);
                                        }
                                    }),

                                TextInput::make('unit_price')
                                    ->label('Est. Unit Price')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(),

                                TextInput::make('total_price')
                                    ->label('Est. Total')
                                    ->numeric()
                                    ->prefix('KES')
                                    ->disabled()
                                    ->dehydrated(),

                                Textarea::make('dosage_instructions')
                                    ->required()
                                    ->placeholder('e.g., Take 1 tablet twice daily after meals')
                                    ->rows(2)
                                    ->columnSpanFull(),

                                TextInput::make('frequency')
                                    ->placeholder('e.g., Twice daily, Every 8 hours')
                                    ->maxLength(255),

                                TextInput::make('duration_days')
                                    ->label('Duration (Days)')
                                    ->numeric()
                                    ->minValue(1)
                                    ->suffix('days'),

                                Textarea::make('notes')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->placeholder('Special instructions for this medicine'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Add Medicine')
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['medicine_id'] 
                                    ? Medicine::find($state['medicine_id'])?->generic_name 
                                    : null
                            ),
                    ])
                    ->columnSpanFull(),

                Section::make('Summary')
                    ->schema([
                        Placeholder::make('estimated_total')
                            ->label('Estimated Total Cost')
                            ->content(function (Get $get) {
                                $items = $get('items') ?? [];
                                $total = collect($items)->sum('total_price');
                                return 'KES ' . number_format($total, 2);
                            }),
                    ])
                    ->visible(fn (Get $get) => !empty($get('items'))),
            ]);
    }
}
