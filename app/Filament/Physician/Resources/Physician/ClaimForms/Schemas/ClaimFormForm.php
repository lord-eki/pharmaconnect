<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Schemas;

use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\Prescription;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
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
                                ->columnSpanFull()
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (! $state) {
                                        // Clear everything if deselected
                                        $set('patient_id', null);
                                        $set('physician_id', null);
                                        $set('insurance_provider_id', null);
                                        $set('_patient_display', null);
                                        $set('_insurance_display', null);
                                        $set('has_template', false);
                                        $set('template_info', null);
                                        $set('diagnosis', null);
                                        $set('treatment_notes', null);
                                        return;
                                    }

                                    $prescription = Prescription::with([
                                        'patient.insuranceProvider.activeFormTemplate',
                                        'physician',
                                        'items.medicine',
                                    ])->find($state);

                                    if (! $prescription) {
                                        return;
                                    }

                                    // Hidden IDs for saving
                                    $set('patient_id', $prescription->patient_id);
                                    $set('physician_id', $prescription->physician_id);

                                    // Patient display
                                    $patient = $prescription->patient;
                                    if ($patient) {
                                        $set('_patient_display', "{$patient->patient_number} — {$patient->first_name} {$patient->last_name}");
                                    }

                                    // Insurance provider display + hidden ID
                                    $provider = $patient?->insuranceProvider;
                                    if ($provider) {
                                        $set('insurance_provider_id', $provider->id);
                                        $set('_insurance_display', $provider->company_name);

                                        if ($provider->activeFormTemplate) {
                                            $template = $provider->activeFormTemplate;
                                            $set('has_template', true);
                                            $set('template_info', "{$template->template_name} (v{$template->version})");
                                        } else {
                                            $set('has_template', false);
                                            $set('template_info', null);
                                        }
                                    } else {
                                        $set('insurance_provider_id', null);
                                        $set('_insurance_display', 'No insurance on file for this patient');
                                        $set('has_template', false);
                                        $set('template_info', null);
                                    }

                                    // Auto-fill clinical info
                                    if ($prescription->diagnosis) {
                                        $set('diagnosis', $prescription->diagnosis);
                                    }

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
                                            $medicinesList .= "  Qty: {$item->quantity}";
                                            if ($item->frequency) {
                                                $medicinesList .= ", Dosage: {$item->frequency}";
                                            }
                                            if ($item->duration_days) {
                                                $medicinesList .= ", Duration: {$item->duration_days} days";
                                            }
                                            $medicinesList .= "\n";
                                            if ($item->dosage_instructions) {
                                                $medicinesList .= "  Instructions: {$item->dosage_instructions}\n";
                                            }
                                        }
                                        $treatmentNotes .= $medicinesList;
                                    }

                                    $set('treatment_notes', trim($treatmentNotes));
                                }),

                            // 2. Patient — read-only display, value stored via Hidden
                            TextInput::make('_patient_display')
                                ->label('Patient')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Select a prescription first'),

                            // 3. Insurance Provider — read-only display, value stored via Hidden
                            TextInput::make('_insurance_display')
                                ->label('Insurance Provider')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Auto-filled from patient'),

                            // Hidden fields that carry the actual IDs to be saved
                            Hidden::make('patient_id'),
                            Hidden::make('insurance_provider_id'),

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
                                ->live(),

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
                            ->rows(2)
                            ->maxLength(1000),

                        Textarea::make('treatment_notes')
                            ->label('Treatment Notes')
                            ->rows(3)
                            ->maxLength(2000),
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