<?php

namespace App\Filament\Resources\InsuranceClaims\Schemas;

use App\Models\InsuranceProvider;
use App\Models\Patient;
use App\Models\Prescription;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class InsuranceClaimForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                Section::make('Claim Information')
                    ->schema([
                        TextInput::make('claim_number')
                            ->label('Claim Number')
                            ->disabled()
                            ->dehydrated()
                            ->default(fn () => \App\Models\InsuranceClaim::generateClaimNumber())
                            ->helperText('Auto-generated upon creation'),

                        Select::make('prescription_id')
                            ->label('Prescription')
                            ->relationship('prescription', 'prescription_number')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                if (!$state) return;

                                $prescription = Prescription::with([
                                    'patient.insuranceProvider',
                                    'items.medicine',
                                    'orders.items'
                                ])->find($state);

                                if (!$prescription) return;

                                // Auto-populate patient
                                $set('patient_id', $prescription->patient_id);

                                // Auto-populate insurance provider
                                if ($prescription->patient->insurance_provider_id) {
                                    $set('insurance_provider_id', $prescription->patient->insurance_provider_id);
                                }

                                // Auto-populate policy number
                                if ($prescription->patient->insurance_number) {
                                    $set('policy_number', $prescription->patient->insurance_number);
                                }

                                // Calculate claimed amount from orders
                                $claimedAmount = $prescription->orders->sum('total_amount');
                                $set('claimed_amount', $claimedAmount);

                                // Set default status
                                if (!$get('status')) {
                                    $set('status', 'submitted');
                                }
                            })
                            ->helperText('Select the prescription for which this claim is being filed'),

                        Select::make('patient_id')
                            ->label('Patient')
                            ->relationship('patient', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->first_name} {$record->last_name} - {$record->patient_number}")
                            ->searchable(['first_name', 'last_name', 'patient_number'])
                            ->preload()
                            ->required()
                            ->disabled(fn (Get $get) => (bool) $get('prescription_id'))
                            ->helperText('Auto-populated from prescription'),

                        Select::make('insurance_provider_id')
                            ->label('Insurance Provider')
                            ->relationship('insuranceProvider', 'company_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->disabled(fn (Get $get) => (bool) $get('prescription_id'))
                            ->helperText('Auto-populated from patient record'),

                        TextInput::make('policy_number')
                            ->label('Policy/Member Number')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Patient\'s insurance policy number'),

                        DatePicker::make('submitted_at')
                            ->label('Submission Date')
                            ->default(now())
                            ->required()
                            ->maxDate(now()),
                    ])
                    ->columns(2),

                Section::make('Prescription Details')
                    ->schema([
                        Placeholder::make('prescription_details')
                            ->label('')
                            ->content(function (Get $get) {
                                $prescriptionId = $get('prescription_id');
                                if (!$prescriptionId) {
                                    return 'Select a prescription to view details';
                                }

                                $prescription = Prescription::with([
                                    'physician',
                                    'items.medicine'
                                ])->find($prescriptionId);

                                if (!$prescription) return 'Prescription not found';

                                $items = $prescription->items->map(function ($item) {
                                    return "• {$item->medicine->generic_name} - {$item->quantity} units @ KES " . 
                                           number_format($item->unit_price, 2) . " = KES " . 
                                           number_format($item->total_price, 2);
                                })->join("\n");

                                return "Prescription: {$prescription->prescription_number}\n" .
                                       "Physician: {$prescription->physician->name}\n" .
                                       "Diagnosis: {$prescription->diagnosis}\n" .
                                       "Prescribed: {$prescription->prescribed_at->format('M d, Y')}\n\n" .
                                       "Medicines:\n{$items}";
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get) => (bool) $get('prescription_id'))
                    ->collapsible()
                    ->collapsed(false),

                Section::make('Order Details')
                    ->schema([
                        ViewField::make('order_details')
                            ->label('')
                            ->view('filament.forms.components.insurance-claim-order-details')
                            ->viewData(fn (Get $get) => [
                                'prescription_id' => $get('prescription_id'),
                            ])
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get) => (bool) $get('prescription_id'))
                    ->collapsible()
                    ->collapsed(true),

                Section::make('Claim Amounts')
                    ->schema([
                        TextInput::make('claimed_amount')
                            ->label('Claimed Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->required()
                            ->live()
                            ->helperText('Total amount being claimed from insurance'),

                        TextInput::make('deductible_amount')
                            ->label('Deductible Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->default(0)
                            ->required()
                            ->live()
                            ->helperText('Patient\'s deductible/co-pay amount'),

                        Placeholder::make('net_claim')
                            ->label('Net Claim Amount')
                            ->content(function (Get $get) {
                                $claimed = (float) ($get('claimed_amount') ?? 0);
                                $deductible = (float) ($get('deductible_amount') ?? 0);
                                $net = $claimed - $deductible;
                                return 'KES ' . number_format($net, 2);
                            }),

                        TextInput::make('approved_amount')
                            ->label('Approved Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->helperText('Amount approved by insurance (filled during review)')
                            ->disabled(fn (Get $get) => $get('status') === 'submitted'),

                        Select::make('status')
                            ->label('Claim Status')
                            ->options([
                                'submitted' => 'Submitted',
                                'under_review' => 'Under Review',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'paid' => 'Paid',
                            ])
                            ->default('submitted')
                            ->required()
                            ->live(),
                    ])
                    ->columns(2),

                Section::make('Review Information')
                    ->schema([
                        DatePicker::make('reviewed_at')
                            ->label('Review Date')
                            ->disabled(),

                        Select::make('reviewed_by')
                            ->label('Reviewed By')
                            ->relationship('reviewer', 'name')
                            ->disabled(),

                        Textarea::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->rows(3)
                            ->columnSpanFull()
                            ->visible(fn (Get $get) => $get('status') === 'rejected')
                            ->required(fn (Get $get) => $get('status') === 'rejected'),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Any additional information about this claim'),
                    ])
                    ->columns(2)
                    ->visible(fn (Get $get) => in_array($get('status'), ['under_review', 'approved', 'rejected', 'paid']))
                    ->collapsible(),

                Section::make('Supporting Documents')
                    ->schema([
                        FileUpload::make('attachments')
                            ->label('Upload Documents')
                            ->multiple()
                            ->disk('public')
                            ->directory('insurance-claims')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120)
                            ->helperText('Upload prescription copies, invoices, or other supporting documents (PDF or images, max 5MB each)')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

            ]);
    }
}