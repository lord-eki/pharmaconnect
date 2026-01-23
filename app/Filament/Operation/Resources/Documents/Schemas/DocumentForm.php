<?php

namespace App\Filament\Operation\Resources\Documents\Schemas;

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
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Tabs::make('Document Management')
                    ->tabs([
                        Tabs\Tab::make('Basic Information')
                            ->schema([
                                Section::make('Document Details')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('document_number')
                                                ->label('Document Number')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->placeholder('Auto-generated'),

                                            Select::make('category_id')
                                                ->label('Category')
                                                ->relationship('category', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload()
                                                ->createOptionForm([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Textarea::make('description')
                                                        ->rows(2),
                                                    Toggle::make('is_active')
                                                        ->default(true),
                                                ]),

                                            Select::make('document_type')
                                                ->label('Document Type')
                                                ->options([
                                                    'claim_form' => 'Claim Form',
                                                    'prescription' => 'Prescription',
                                                    'invoice' => 'Invoice',
                                                    'receipt' => 'Receipt',
                                                    'delivery_note' => 'Delivery Note',
                                                    'credit_note' => 'Credit Note',
                                                    'purchase_order' => 'Purchase Order',
                                                    'payment_voucher' => 'Payment Voucher',
                                                    'contract' => 'Contract',
                                                    'agreement' => 'Agreement',
                                                    'report' => 'Report',
                                                    'other' => 'Other',
                                                ])
                                                ->required()
                                                ->searchable()
                                                ->native(false),
                                        ]),

                                        Grid::make(2)->schema([
                                            TextInput::make('title')
                                                ->label('Document Title')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),

                                            Textarea::make('description')
                                                ->label('Description')
                                                ->rows(3)
                                                ->columnSpanFull()
                                                ->helperText('Provide a detailed description of the document'),
                                        ]),
                                    ]),

                                Section::make('File Upload')
                                    ->schema([
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
                                            ->columnSpanFull()
                                            ->helperText('Accepted formats: PDF, JPG, PNG (Max: 10MB)')
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                                                    $set('file_name', $state->getClientOriginalName());
                                                    $set('mime_type', $state->getMimeType());
                                                    $set('file_size', $state->getSize());
                                                    $set('file_hash', hash_file('sha256', $state->getRealPath()));
                                                    $set('uploaded_by', Auth::id());
                                                    $set('uploaded_at', now());
                                                }
                                            }),

                                        Grid::make(2)->schema([
                                            TextInput::make('file_name')
                                                ->label('File Name')
                                                ->disabled()
                                                ->dehydrated(),

                                            TextInput::make('mime_type')
                                                ->label('File Type')
                                                ->disabled()
                                                ->dehydrated(),
                                        ])->visibleOn('edit'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Related Entities')
                            ->schema([
                                Section::make('Link to Records')
                                    ->description('Connect this document to related system records')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('prescription_id')
                                                ->label('Prescription')
                                                ->relationship('prescription', 'prescription_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->prescription_number} - {$record->patient->full_name}"
                                                ),

                                            Select::make('order_id')
                                                ->label('Order')
                                                ->relationship('order', 'order_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->order_number} - {$record->created_at->format('d/m/Y')}"
                                                ),

                                            Select::make('insurance_claim_id')
                                                ->label('Insurance Claim')
                                                ->relationship('insuranceClaim', 'claim_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->claim_number} - {$record->patient->full_name}"
                                                ),

                                            Select::make('supplier_id')
                                                ->label('Supplier')
                                                ->relationship('supplier', 'company_name')
                                                ->searchable()
                                                ->preload(),

                                            Select::make('insurance_provider_id')
                                                ->label('Insurance Provider')
                                                ->relationship('insuranceProvider', 'company_name')
                                                ->searchable()
                                                ->preload(),

                                            Select::make('patient_id')
                                                ->label('Patient')
                                                ->relationship('patient', 'patient_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->patient_number} - {$record->first_name} {$record->last_name}"
                                                ),
                                        ]),
                                    ]),

                                Section::make('Tags & Metadata')
                                    ->schema([
                                        TagsInput::make('tags')
                                            ->label('Document Tags')
                                            ->placeholder('Add tags for easy searching')
                                            ->helperText('Press Enter to add a tag')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tabs\Tab::make('Verification & Status')
                            ->schema([
                                Section::make('Document Verification')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('verification_status')
                                                ->label('Verification Status')
                                                ->options([
                                                    'pending' => 'Pending Review',
                                                    'verified' => 'Verified',
                                                    'rejected' => 'Rejected',
                                                ])
                                                ->default('pending')
                                                ->required()
                                                ->native(false),

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
                                        ]),

                                        Textarea::make('verification_notes')
                                            ->label('Verification Notes')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->placeholder('Add notes about verification or rejection'),
                                    ])
                                    ->visibleOn('edit'),

                                Section::make('Document Status')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('status')
                                                ->label('Status')
                                                ->options([
                                                    'active' => 'Active',
                                                    'archived' => 'Archived',
                                                    'deleted' => 'Deleted',
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
                                    ])
                                    ->visibleOn('edit'),
                            ]),

                        Tabs\Tab::make('Version History')
                            ->schema([
                                Section::make('Document Versions')
                                    ->description('View and manage document version history')
                                    ->schema([
                                        Repeater::make('versionHistory')
                                            ->relationship('versionHistory')
                                            ->schema([
                                                Grid::make(3)->schema([
                                                    TextInput::make('version_number')
                                                        ->label('Version')
                                                        ->disabled(),
                                                    
                                                    TextInput::make('created_at')
                                                        ->label('Created At')
                                                        ->disabled(),
                                                    
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
                                    ->visibleOn('edit')
                                    ->collapsible(),
                            ])
                            ->visibleOn('edit'),

                        Tabs\Tab::make('Access & Sharing')
                            ->schema([
                                Section::make('Document Sharing')
                                    ->description('Manage who has access to this document')
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
                                                        ->label('Permission Level')
                                                        ->options([
                                                            'view' => 'View Only',
                                                            'download' => 'View & Download',
                                                            'edit' => 'Full Access',
                                                        ])
                                                        ->default('view')
                                                        ->required(),

                                                    DateTimePicker::make('expires_at')
                                                        ->label('Expires At')
                                                        ->placeholder('Never expires'),

                                                    Toggle::make('is_active')
                                                        ->label('Active')
                                                        ->default(true),
                                                ]),

                                                Textarea::make('notes')
                                                    ->label('Sharing Notes')
                                                    ->rows(2)
                                                    ->columnSpanFull(),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible(),
                                    ])
                                    ->visibleOn('edit'),
                            ])
                            ->visibleOn('edit'),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
            ]);
    }
}
