<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Schemas;

use App\Models\InsuranceProvider;
use App\Models\Prescription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class ClaimFormForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Claim Form Information')
                    ->description('Complete the claim form for insurance submission')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('form_number')
                                ->label('Form Number')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($context) => $context === 'edit'),

                            Select::make('prescription_id')
                                ->label('Prescription')
                                ->relationship('prescription', 'prescription_number')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $prescription = Prescription::with(['patient', 'physician', 'items.medicine'])
                                            ->find($state);
                                        
                                        if ($prescription) {
                                            // Set patient and physician
                                            $set('patient_id', $prescription->patient_id);
                                            $set('physician_id', $prescription->physician_id);
                                            
                                            // Auto-fill clinical information
                                            if ($prescription->diagnosis) {
                                                $set('diagnosis', $prescription->diagnosis);
                                            }
                                            
                                            // Build treatment notes from prescription items
                                            $treatmentNotes = $prescription->notes ?? '';
                                            
                                            if ($prescription->items->isNotEmpty()) {
                                                $medicinesList = "\n\nPrescribed Medicines:\n";
                                                foreach ($prescription->items as $item) {
                                                    $medicine = $item->medicine;
                                                    $medicinesList .= "- {$medicine->generic_name}";
                                                    if ($medicine->brand_name) {
                                                        $medicinesList .= " ({$medicine->brand_name})";
                                                    }
                                                    $medicinesList .= " - {$medicine->strength}\n";
                                                    $medicinesList .= "  Qty: {$item->quantity}, ";
                                                    if ($item->frequency) {
                                                        $medicinesList .= "Dosage: {$item->frequency}, ";
                                                    }
                                                    if ($item->duration_days) {
                                                        $medicinesList .= "Duration: {$item->duration_days} days";
                                                    }
                                                    $medicinesList .= "\n";
                                                    if ($item->dosage_instructions) {
                                                        $medicinesList .= "  Instructions: {$item->dosage_instructions}\n";
                                                    }
                                                }
                                                $treatmentNotes .= $medicinesList;
                                            }
                                            
                                            $set('treatment_notes', trim($treatmentNotes));
                                            
                                            // Auto-fill insurance provider if patient has one
                                            if ($prescription->patient && $prescription->patient->insurance_provider_id) {
                                                $set('insurance_provider_id', $prescription->patient->insurance_provider_id);
                                            }
                                        }
                                    }
                                }),

                            Select::make('insurance_provider_id')
                                ->label('Insurance Provider')
                                ->relationship('insuranceProvider', 'company_name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state) {
                                        $provider = InsuranceProvider::find($state);
                                        $set('form_template', $provider->form_template ?? null);
                                    }
                                }),

                            Select::make('patient_id')
                                ->label('Patient')
                                ->relationship('patient', 'patient_number')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->patient_number} - {$record->first_name} {$record->last_name}"),

                            Select::make('physician_id')
                                ->label('Physician')
                                ->relationship('physician', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->default(fn () => auth()->id()),

                            Select::make('submission_type')
                                ->label('Submission Type')
                                ->options([
                                    'online' => 'Online (Electronic)',
                                    'manual' => 'Manual (Scanned)',
                                ])
                                ->default('online')
                                ->required()
                                ->live()
                                ->helperText('Online: Fill form electronically. Manual: Upload scanned physical form.'),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'draft' => 'Draft',
                                    'submitted' => 'Submitted',
                                    'processing' => 'Processing',
                                    'approved' => 'Approved',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('draft')
                                ->required()
                                ->disabled(fn ($context) => $context === 'create'),
                        ]),
                    ]),

                Section::make('Clinical Information')
                    ->description('Clinical details from prescription (editable)')
                    ->schema([
                        Textarea::make('diagnosis')
                            ->label('Diagnosis')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Primary diagnosis - auto-filled from prescription'),

                        Textarea::make('treatment_notes')
                            ->label('Treatment Notes')
                            ->rows(5)
                            ->maxLength(2000)
                            ->helperText('Treatment plan and prescribed medicines - auto-filled from prescription'),
                    ]),

                Section::make('Manual Form Upload')
                    ->description('Upload scanned physical claim form (only for manual submissions)')
                    ->schema([
                        FileUpload::make('document_path')
                            ->label('Upload Scanned Form')
                            ->disk('local')
                            ->directory('claim-forms')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->previewable()
                            ->helperText('Upload the scanned claim form if offline system was used')
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if ($state && $get('submission_type') === 'manual') {
                                    $set('has_uploaded_document', true);
                                }
                            }),
                    ])
                    ->visible(fn (Get $get) => $get('submission_type') === 'manual')
                    ->collapsible(),

            ]);
    }
}