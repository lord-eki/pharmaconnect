<?php

namespace App\Filament\Operation\Resources\Documents\Schemas;

use App\Models\Prescription;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([

                // ── Single main section ───────────────────────────────────────
                Section::make()
                    ->schema([
                        Grid::make(4)->schema([

                            // Row 1: Document #, Type, Category (x2)
                            TextInput::make('document_number')
                                ->label('Document #')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('Auto-generated'),

                            Select::make('document_type')
                                ->label('Type')
                                ->options([
                                    'claim_form'      => 'Claim Form',
                                    'prescription'    => 'Prescription',
                                    'invoice'         => 'Invoice',
                                    'receipt'         => 'Receipt',
                                    'delivery_note'   => 'Delivery Note',
                                    'credit_note'     => 'Credit Note',
                                    'purchase_order'  => 'Purchase Order',
                                    'payment_voucher' => 'Payment Voucher',
                                    'contract'        => 'Contract',
                                    'agreement'       => 'Agreement',
                                    'report'          => 'Report',
                                    'other'           => 'Other',
                                ])
                                ->required()
                                ->searchable()
                                ->native(false),

                            Select::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload()
                                ->createOptionForm([
                                    TextInput::make('name')->required()->maxLength(255),
                                    Textarea::make('description')->rows(2),
                                    Toggle::make('is_active')->default(true),
                                ])
                                ->columnSpan(2),

                            // Row 2: Title (x3), Verification Status
                            TextInput::make('title')
                                ->label('Document Title')
                                ->required()
                                ->maxLength(255)
                                ->columnSpan(3),

                            Select::make('verification_status')
                                ->label('Verification Status')
                                ->options([
                                    'pending'  => 'Pending Review',
                                    'verified' => 'Verified',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('pending')
                                ->required()
                                ->native(false),

                            // Row 3: Prescription (x2), Order, Supplier
                            Select::make('prescription_id')
                                ->label('Prescription')
                                ->relationship(
                                    name: 'prescription',
                                    titleAttribute: 'prescription_number',
                                    modifyQueryUsing: fn ($query) => $query->with(['patient', 'patient.insuranceProvider'])
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    "{$record->prescription_number} — " . ($record->patient?->full_name ?? 'No Patient')
                                )
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (! $state) {
                                        $set('patient_id', null);
                                        $set('insurance_provider_id', null);
                                        $set('insurance_claim_id', null);
                                        $set('_patient_name', null);
                                        $set('_insurance_name', null);
                                        $set('_claim_number', null);
                                        return;
                                    }

                                    $prescription = Prescription::with([
                                        'patient.insuranceProvider',
                                        'insuranceClaim',
                                    ])->find($state);

                                    if (! $prescription) return;

                                    $set('patient_id', $prescription->patient_id);
                                    $set('insurance_provider_id', $prescription->patient?->insurance_provider_id);
                                    $set('insurance_claim_id', $prescription->insuranceClaim?->id);
                                    $set('_patient_name', $prescription->patient?->full_name ?? '—');
                                    $set('_insurance_name', $prescription->patient?->insuranceProvider?->company_name ?? '—');
                                    $set('_claim_number', $prescription->insuranceClaim?->claim_number ?? '—');
                                })
                                ->columnSpan(2),

                            Select::make('order_id')
                                ->label('Order')
                                ->relationship(
                                    name: 'order',
                                    titleAttribute: 'order_number',
                                    modifyQueryUsing: fn ($query) => $query->with('supplier')
                                )
                                ->searchable()
                                ->preload()
                                ->live()
                                ->getOptionLabelFromRecordUsing(fn ($record) =>
                                    "{$record->order_number} — {$record->created_at->format('d/m/Y')}"
                                )
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if (! $state) {
                                        $set('supplier_id', null);
                                        return;
                                    }
                                    $order = \App\Models\Order::find($state);
                                    if ($order?->supplier_id) {
                                        $set('supplier_id', $order->supplier_id);
                                    }
                                }),

                            Select::make('supplier_id')
                                ->label('Supplier')
                                ->relationship('supplier', 'company_name')
                                ->searchable()
                                ->preload(),

                            // Row 4: auto-filled fields — only visible when prescription is selected
                            TextInput::make('_patient_name')
                                ->label('Patient')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('—')
                                ->visible(fn ($get) => filled($get('prescription_id'))),

                            TextInput::make('_insurance_name')
                                ->label('Insurance')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('—')
                                ->visible(fn ($get) => filled($get('prescription_id'))),

                            TextInput::make('_claim_number')
                                ->label('Claim #')
                                ->disabled()
                                ->dehydrated(false)
                                ->placeholder('—')
                                ->visible(fn ($get) => filled($get('prescription_id'))),

                            FileUpload::make('file_path')
                                ->label('Upload Document')
                                ->disk('local')
                                ->directory('documents')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->maxSize(10240)
                                ->required()
                                ->downloadable()
                                ->previewable()
                                ->openable()
                                ->helperText('PDF, JPG, PNG — max 10MB')
                                ->afterStateUpdated(function ($state, Set $set) {
                                    if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                                        $set('file_name', $state->getClientOriginalName());
                                        $set('mime_type', $state->getMimeType());
                                        $set('file_size', $state->getSize());
                                        $set('file_hash', hash_file('sha256', $state->getRealPath()));
                                        $set('uploaded_by', Auth::id());
                                        $set('uploaded_at', now());
                                    }
                                })
                                ->columnSpan(3),

                            // Tags beside file upload
                            TagsInput::make('tags')
                                ->label('Tags')
                                ->placeholder('Press Enter to add'),

                            // Description full width
                            Textarea::make('description')
                                ->label('Description')
                                ->rows(2)
                                ->placeholder('Optional notes about this document')
                                ->columnSpanFull(),

                            // Hidden fields
                            TextInput::make('file_name')->hidden()->dehydrated(),
                            TextInput::make('mime_type')->hidden()->dehydrated(),
                            TextInput::make('file_size')->hidden()->dehydrated(),
                            TextInput::make('file_hash')->hidden()->dehydrated(),
                            TextInput::make('patient_id')->hidden()->dehydrated(),
                            TextInput::make('insurance_provider_id')->hidden()->dehydrated(),
                            TextInput::make('insurance_claim_id')->hidden()->dehydrated(),
                        ]),
                    ])->columnSpanFull(),

                Section::make('Verification & Status')
                    ->schema([
                        Grid::make(4)->schema([
                            Select::make('verified_by')
                                ->label('Verified By')
                                ->relationship('verifiedBy', 'name')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record?->verified_by),

                            DateTimePicker::make('verified_at')
                                ->label('Verified At')
                                ->disabled()
                                ->dehydrated(false)
                                ->visible(fn ($record) => $record?->verified_at),

                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    'active'   => 'Active',
                                    'archived' => 'Archived',
                                    'deleted'  => 'Deleted',
                                ])
                                ->default('active')
                                ->required()
                                ->native(false),

                            Toggle::make('is_locked')
                                ->label('Lock Document')
                                ->helperText('Locked documents cannot be edited or deleted')
                                ->default(false)
                                ->inline(false),
                        ]),

                        Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->rows(2)
                            ->placeholder('Add notes about verification or rejection')
                            ->columnSpanFull(),
                    ])
                    ->visibleOn('edit'),

                Section::make('Version History')
                    ->schema([
                        Repeater::make('versionHistory')
                            ->relationship('versionHistory')
                            ->schema([
                                Grid::make(3)->schema([
                                    TextInput::make('version_number')->label('Version')->disabled(),
                                    TextInput::make('created_at')->label('Created At')->disabled(),
                                    Select::make('created_by')
                                        ->label('Created By')
                                        ->relationship('createdBy', 'name')
                                        ->disabled(),
                                ]),
                                Textarea::make('change_notes')
                                    ->label('Change Notes')
                                    ->rows(2)
                                    ->columnSpanFull()
                                    ->disabled(),
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('edit'),

                Section::make('Access & Sharing')
                    ->schema([
                        Repeater::make('shares')
                            ->relationship('shares')
                            ->schema([
                                Grid::make(4)->schema([
                                    Select::make('shared_with')
                                        ->label('Share With')
                                        ->relationship('sharedWith', 'name')
                                        ->searchable()
                                        ->required(),

                                    Select::make('permission')
                                        ->label('Permission')
                                        ->options([
                                            'view'     => 'View Only',
                                            'download' => 'View & Download',
                                            'edit'     => 'Full Access',
                                        ])
                                        ->default('view')
                                        ->required(),

                                    DateTimePicker::make('expires_at')
                                        ->label('Expires At')
                                        ->placeholder('Never'),

                                    Toggle::make('is_active')
                                        ->label('Active')
                                        ->default(true),
                                ]),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->collapsible()
                    ->collapsed()
                    ->visibleOn('edit'),

            ]);
    }
}