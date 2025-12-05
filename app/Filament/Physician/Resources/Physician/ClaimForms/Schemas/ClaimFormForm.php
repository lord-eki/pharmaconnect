<?php

namespace App\Filament\Physician\Resources\Physician\ClaimForms\Schemas;

use App\Models\InsuranceProvider;
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
                                        $prescription = \App\Models\Prescription::find($state);
                                        if ($prescription) {
                                            $set('patient_id', $prescription->patient_id);
                                            $set('physician_id', $prescription->physician_id);
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
                    ->schema([
                        Textarea::make('diagnosis')
                            ->label('Diagnosis')
                            ->required()
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Primary diagnosis and ICD-10 code if applicable'),

                        Textarea::make('treatment_notes')
                            ->label('Treatment Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Treatment plan and clinical notes'),
                    ]),

                Section::make('Insurance-Specific Fields')
                    ->description('Fields customized for the selected insurance provider')
                    ->schema([
                        KeyValue::make('form_data')
                            ->label('Additional Fields')
                            ->keyLabel('Field Name')
                            ->valueLabel('Value')
                            ->helperText('Add any additional fields required by the insurance provider')
                            ->addActionLabel('Add Field')
                            ->reorderable(),
                    ])
                    ->collapsible(),

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
                                // Automatically create document record when file is uploaded
                                if ($state && $get('submission_type') === 'manual') {
                                    // This will be handled in the page class
                                    $set('has_uploaded_document', true);
                                }
                            }),
                    ])
                    ->visible(fn (Get $get) => $get('submission_type') === 'manual')
                    ->collapsible(),

                Section::make('Digital Signatures')
                    ->description('Electronic signatures (for online submissions)')
                    ->schema([
                        Grid::make(2)->schema([
                            Textarea::make('physician_signature')
                                ->label('Physician Signature')
                                ->rows(3)
                                ->helperText('Digital signature or typed name confirmation')
                                ->visible(fn (Get $get) => $get('submission_type') === 'online'),

                            Textarea::make('patient_signature')
                                ->label('Patient Signature')
                                ->rows(3)
                                ->helperText('Digital signature or typed consent')
                                ->visible(fn (Get $get) => $get('submission_type') === 'online'),

                            DateTimePicker::make('signed_at')
                                ->label('Signed At')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($context) => $context === 'edit'),
                        ]),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }
}
