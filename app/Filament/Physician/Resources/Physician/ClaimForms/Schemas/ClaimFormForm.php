<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Schemas;

use App\Models\InsuranceProvider;
use App\Models\Prescription;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Storage;

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
                                        $prescription = Prescription::with(['patient.insuranceProvider', 'physician', 'items.medicine'])
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
                                                
                                                // Check if provider has template
                                                $provider = InsuranceProvider::with('activeFormTemplate')
                                                    ->find($prescription->patient->insurance_provider_id);
                                                
                                                if ($provider && $provider->activeFormTemplate) {
                                                    $set('has_template', true);
                                                    $set('template_info', $provider->activeFormTemplate->template_name);
                                                } else {
                                                    $set('has_template', false);
                                                }
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
                                        $provider = InsuranceProvider::with('activeFormTemplate')->find($state);
                                        
                                        if ($provider && $provider->activeFormTemplate) {
                                            $template = $provider->activeFormTemplate;
                                            $set('form_template', $template->template_name);
                                            $set('has_template', true);
                                            $set('template_info', "{$template->template_name} (v{$template->version})");
                                        } else {
                                            $set('has_template', false);
                                            $set('template_info', null);
                                        }
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
                                    'online' => 'Online (Auto-Generate Form)',
                                    'manual' => 'Manual (Upload Scanned Form)',
                                ])
                                ->default('online')
                                ->required()
                                ->live()
                                ->helperText(fn (Get $get) => 
                                    $get('submission_type') === 'online' 
                                        ? '✓ Form will be automatically generated using insurance provider template'
                                        : 'Upload a scanned physical form'
                                ),

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

                // Template Information Section
                Section::make('Form Template Information')
                    ->description('Insurance provider form template details')
                    ->schema([
                        Placeholder::make('template_info')
                            ->label('Active Template')
                            ->content(fn (Get $get) => $get('template_info') ?? 'No template configured for this provider'),
                        
                        Placeholder::make('template_status')
                            ->label('Generation Status')
                            ->content(fn (Get $get) => 
                                $get('has_template') 
                                    ? '✓ Form will be auto-generated when you create this claim'
                                    : '⚠ No template available - please configure template or use manual submission'
                            )
                            ->helperText(fn (Get $get) => 
                                !$get('has_template') && $get('insurance_provider_id')
                                    ? 'Contact admin to set up form template for this insurance provider'
                                    : null
                            ),
                    ])
                    ->visible(fn (Get $get) => 
                        $get('submission_type') === 'online' && $get('insurance_provider_id')
                    )
                    ->collapsible(),

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
                            ->directory('claim-forms/manual')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                            ->maxSize(5120)
                            ->downloadable()
                            ->previewable()
                            ->helperText('Upload the completed and scanned claim form')
                            ->required(fn (Get $get) => $get('submission_type') === 'manual'),
                    ])
                    ->visible(fn (Get $get) => $get('submission_type') === 'manual')
                    ->collapsible(),

                // Show generated document in edit mode
                Section::make('Generated Claim Form')
                    ->description('Auto-generated insurance claim form')
                    ->schema([
                        Placeholder::make('generated_info')
                            ->label('Generation Details')
                            ->content(fn ($record) => 
                                $record && $record->generated_at
                                    ? "Generated on {$record->generated_at->format('M d, Y H:i')} using {$record->template_used}"
                                    : 'Not yet generated'
                            ),
                        
                        FileUpload::make('generated_document_path')
                            ->label('Generated Form')
                            ->disk('local')
                            ->downloadable()
                            ->previewable()
                            ->disabled()
                            ->dehydrated(false)
                            ->visible(fn ($record) => $record && $record->generated_document_path),
                    ])
                    ->visible(fn ($context, $record) => 
                        $context === 'edit' && 
                        $record && 
                        $record->submission_type === 'online'
                    )
                    ->collapsible(),

            ]);
    }
}