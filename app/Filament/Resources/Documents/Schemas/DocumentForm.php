<?php

namespace App\Filament\Resources\Documents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
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
                      Tabs::make('Complete Document Management')
                    ->tabs([
                        Tabs\Tab::make('Document Information')
                            ->icon('heroicon-o-document-text')
                            ->schema([
                                Section::make('Basic Details')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            TextInput::make('document_number')
                                                ->label('Document Number')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->placeholder('Auto-generated on save'),

                                            Select::make('category_id')
                                                ->label('Category')
                                                ->relationship('category', 'name')
                                                ->required()
                                                ->searchable()
                                                ->preload()
                                                ->createOptionForm([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255)
                                                        ->unique('document_categories', 'name'),
                                                    Textarea::make('description')
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                    Grid::make(2)->schema([
                                                        Toggle::make('is_active')
                                                            ->label('Active')
                                                            ->default(true),
                                                        TextInput::make('sort_order')
                                                            ->label('Sort Order')
                                                            ->numeric()
                                                            ->default(0),
                                                    ]),
                                                ])
                                                ->editOptionForm([
                                                    TextInput::make('name')
                                                        ->required()
                                                        ->maxLength(255),
                                                    Textarea::make('description')
                                                        ->rows(2)
                                                        ->columnSpanFull(),
                                                    Grid::make(2)->schema([
                                                        Toggle::make('is_active')
                                                            ->label('Active'),
                                                        TextInput::make('sort_order')
                                                            ->label('Sort Order')
                                                            ->numeric(),
                                                    ]),
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
                                                    'compliance_doc' => 'Compliance Document',
                                                    'audit_report' => 'Audit Report',
                                                    'policy_doc' => 'Policy Document',
                                                    'report' => 'Report',
                                                    'other' => 'Other',
                                                ])
                                                ->required()
                                                ->searchable()
                                                ->native(false),
                                        ]),

                                        TextInput::make('title')
                                            ->label('Document Title')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        Textarea::make('description')
                                            ->label('Description')
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->helperText('Detailed description of document content and purpose'),
                                    ]),

                                Section::make('File Management')
                                    ->schema([
                                        FileUpload::make('file_path')
                                            ->label('Upload Document')
                                            ->disk('local')
                                            ->directory('documents')
                                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                            ->maxSize(15360) // 15MB for admin
                                            ->required()
                                            ->downloadable()
                                            ->previewable()
                                            ->openable()
                                            ->columnSpanFull()
                                            ->helperText('Supported: PDF, JPG, PNG (Maximum: 15MB)')
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

                                        Grid::make(4)->schema([
                                            TextInput::make('file_name')
                                                ->label('Original File Name')
                                                ->disabled()
                                                ->dehydrated(),

                                            TextInput::make('mime_type')
                                                ->label('MIME Type')
                                                ->disabled()
                                                ->dehydrated(),

                                            TextInput::make('file_size')
                                                ->label('File Size')
                                                ->disabled()
                                                ->dehydrated()
                                                ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2) . ' KB' : ''),

                                            TextInput::make('file_hash')
                                                ->label('File Hash (SHA-256)')
                                                ->disabled()
                                                ->dehydrated(),
                                        ])->visibleOn('edit'),
                                    ]),
                            ]),

                        Tabs\Tab::make('Entity Relationships')
                            ->icon('heroicon-o-link')
                            ->schema([
                                Section::make('Primary Relationships')
                                    ->description('Link document to main system entities')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('prescription_id')
                                                ->label('Prescription')
                                                ->relationship('prescription', 'prescription_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->prescription_number} - {$record->patient->full_name} ({$record->created_at->format('d/m/Y')})"
                                                ),

                                            Select::make('order_id')
                                                ->label('Order')
                                                ->relationship('order', 'order_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->order_number} - Total: {$record->total_amount} ({$record->created_at->format('d/m/Y')})"
                                                ),

                                            Select::make('insurance_claim_id')
                                                ->label('Insurance Claim')
                                                ->relationship('insuranceClaim', 'claim_number')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->claim_number} - {$record->patient->full_name} - {$record->insuranceProvider->company_name}"
                                                ),

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

                                Section::make('Organization Relationships')
                                    ->description('Link to suppliers and insurance providers')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('supplier_id')
                                                ->label('Supplier')
                                                ->relationship('supplier', 'company_name')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->company_name} - {$record->supplier_code}"
                                                ),

                                            Select::make('insurance_provider_id')
                                                ->label('Insurance Provider')
                                                ->relationship('insuranceProvider', 'company_name')
                                                ->searchable()
                                                ->preload()
                                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                    "{$record->company_name} - {$record->provider_code}"
                                                ),
                                        ]),
                                    ]),

                                Section::make('Version Control')
                                    ->schema([
                                        Grid::make(2)->schema([
                                            Select::make('parent_document_id')
                                                ->label('Parent Document')
                                                ->relationship('parentDocument', 'document_number')
                                                ->searchable()
                                                ->preload()
                                                ->helperText('Select if this is a new version of an existing document'),

                                            TextInput::make('version')
                                                ->label('Version Number')
                                                ->numeric()
                                                ->default(1)
                                                ->minValue(1)
                                                ->helperText('Automatically incremented for version updates'),
                                        ]),
                                    ])
                                    ->collapsible(),

                                Section::make('Metadata & Tags')
                                    ->schema([
                                        TagsInput::make('tags')
                                            ->label('Tags')
                                            ->placeholder('Add searchable tags')
                                            ->helperText('Press Enter after each tag')
                                            ->columnSpanFull(),

                                        KeyValue::make('metadata')
                                            ->label('Custom Metadata')
                                            ->keyLabel('Property')
                                            ->valueLabel('Value')
                                            ->columnSpanFull()
                                            ->helperText('Add custom key-value pairs for extended document properties'),
                                    ])
                                    ->collapsible(),
                            ]),

                        Tabs\Tab::make('Verification & Compliance')
                            ->icon('heroicon-o-shield-check')
                            ->schema([
                                Section::make('Verification Details')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('verification_status')
                                                ->label('Verification Status')
                                                ->options([
                                                    'pending' => 'Pending Review',
                                                    'verified' => 'Verified & Approved',
                                                    'rejected' => 'Rejected',
                                                ])
                                                ->default('pending')
                                                ->required()
                                                ->native(false)
                                                ->live(),

                                            Select::make('verified_by')
                                                ->label('Verified By')
                                                ->relationship('verifiedBy', 'name')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->visible(fn ($record) => $record?->verified_by),

                                            DateTimePicker::make('verified_at')
                                                ->label('Verification Date')
                                                ->disabled()
                                                ->dehydrated(false)
                                                ->visible(fn ($record) => $record?->verified_at),
                                        ]),

                                        Textarea::make('verification_notes')
                                            ->label('Verification Notes / Comments')
                                            ->rows(5)
                                            ->columnSpanFull()
                                            ->placeholder('Add detailed notes about verification, rejection reasons, or compliance notes')
                                            ->helperText('Required for rejected documents'),
                                    ])
                                    ->visibleOn('edit'),

                                Section::make('Document Status & Controls')
                                    ->schema([
                                        Grid::make(3)->schema([
                                            Select::make('status')
                                                ->label('Document Status')
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
                                                ->helperText('Prevents editing and deletion')
                                                ->default(false)
                                                ->inline(false),

                                            DateTimePicker::make('uploaded_at')
                                                ->label('Upload Date')
                                                ->disabled()
                                                ->dehydrated(false),
                                        ]),

                                        Select::make('uploaded_by')
                                            ->label('Uploaded By')
                                            ->relationship('uploadedBy', 'name')
                                            ->disabled()
                                            ->dehydrated(false)
                                            ->visible(fn ($record) => $record?->uploaded_by),
                                    ])
                                    ->visibleOn('edit'),
                            ]),

                        Tabs\Tab::make('Version History')
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Section::make('Document Versions')
                                    ->description('Complete version history and changes')
                                    ->schema([
                                        Repeater::make('versionHistory')
                                            ->relationship('versionHistory')
                                            ->schema([
                                                Grid::make(4)->schema([
                                                    TextInput::make('version_number')
                                                        ->label('Version')
                                                        ->disabled()
                                                        ->formatStateUsing(fn ($state) => "v{$state}"),
                                                    
                                                    TextInput::make('created_at')
                                                        ->label('Date')
                                                        ->disabled()
                                                        ->formatStateUsing(fn ($state) => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : ''),
                                                    
                                                    Select::make('created_by')
                                                        ->label('Modified By')
                                                        ->relationship('createdBy', 'name')
                                                        ->disabled(),

                                                    TextInput::make('file_size')
                                                        ->label('Size')
                                                        ->disabled()
                                                        ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 2) . ' KB' : ''),
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
                                            ->columnSpanFull()
                                            ->defaultItems(0),
                                    ])
                                    ->visibleOn('edit')
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->visibleOn('edit'),

                        Tabs\Tab::make('Access Management')
                            ->icon('heroicon-o-users')
                            ->schema([
                                Section::make('Document Sharing & Permissions')
                                    ->description('Control who can access this document and their permission levels')
                                    ->schema([
                                        Repeater::make('shares')
                                            ->relationship('shares')
                                            ->schema([
                                                Grid::make(5)->schema([
                                                    Select::make('shared_with')
                                                        ->label('User')
                                                        ->relationship('sharedWith', 'name')
                                                        ->searchable()
                                                        ->required()
                                                        ->getOptionLabelFromRecordUsing(fn ($record) => 
                                                            "{$record->name} ({$record->email})"
                                                        ),

                                                    Select::make('permission')
                                                        ->label('Permission')
                                                        ->options([
                                                            'view' => 'View Only',
                                                            'download' => 'View & Download',
                                                            'edit' => 'Full Access (Edit)',
                                                        ])
                                                        ->default('view')
                                                        ->required()
                                                        ->native(false),

                                                    DateTimePicker::make('expires_at')
                                                        ->label('Expiration')
                                                        ->placeholder('No expiration')
                                                        ->helperText('Leave empty for permanent access'),

                                                    Toggle::make('is_active')
                                                        ->label('Active')
                                                        ->default(true)
                                                        ->inline(false),

                                                    Select::make('shared_by')
                                                        ->label('Shared By')
                                                        ->relationship('sharedBy', 'name')
                                                        ->disabled()
                                                        ->dehydrated(false)
                                                        ->visible(fn ($record) => $record?->shared_by),
                                                ]),

                                                Textarea::make('notes')
                                                    ->label('Sharing Notes')
                                                    ->rows(2)
                                                    ->columnSpanFull()
                                                    ->placeholder('Optional notes about why this document was shared'),
                                            ])
                                            ->columnSpanFull()
                                            ->collapsible()
                                            ->itemLabel(fn (array $state): ?string => 
                                                $state['shared_with'] ?? 'New Share'
                                            )
                                            ->defaultItems(0)
                                            ->reorderableWithButtons()
                                            ->cloneable(),
                                    ])
                                    ->visibleOn('edit'),

                                Section::make('Access Activity Log')
                                    ->description('Recent access history for this document')
                                    ->schema([
                                        Repeater::make('accessLogs')
                                            ->relationship('accessLogs')
                                            ->schema([
                                                Grid::make(5)->schema([
                                                    Select::make('user_id')
                                                        ->label('User')
                                                        ->relationship('user', 'name')
                                                        ->disabled(),

                                                    TextInput::make('action')
                                                        ->label('Action')
                                                        ->disabled(),

                                                    TextInput::make('ip_address')
                                                        ->label('IP Address')
                                                        ->disabled(),

                                                    TextInput::make('accessed_at')
                                                        ->label('Date/Time')
                                                        ->disabled()
                                                        ->formatStateUsing(fn ($state) => 
                                                            $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i:s') : ''
                                                        ),

                                                    TextInput::make('user_agent')
                                                        ->label('Browser/Device')
                                                        ->disabled()
                                                        ->columnSpanFull(),
                                                ]),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull()
                                            ->defaultItems(0),
                                    ])
                                    ->visibleOn('edit')
                                    ->collapsible()
                                    ->collapsed(),
                            ])
                            ->visibleOn('edit'),

                        Tabs\Tab::make('Comments & Collaboration')
                            ->icon('heroicon-o-chat-bubble-left-right')
                            ->schema([
                                Section::make('Document Comments')
                                    ->description('Internal comments and discussions about this document')
                                    ->schema([
                                        Repeater::make('comments')
                                            ->relationship('comments')
                                            ->schema([
                                                Grid::make(2)->schema([
                                                    Select::make('user_id')
                                                        ->label('User')
                                                        ->relationship('user', 'name')
                                                        ->disabled(),

                                                    TextInput::make('created_at')
                                                        ->label('Posted At')
                                                        ->disabled()
                                                        ->formatStateUsing(fn ($state) => 
                                                            $state ? \Carbon\Carbon::parse($state)->diffForHumans() : ''
                                                        ),
                                                ]),

                                                Textarea::make('comment')
                                                    ->label('Comment')
                                                    ->rows(3)
                                                    ->columnSpanFull()
                                                    ->disabled(),
                                            ])
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull()
                                            ->defaultItems(0),
                                    ])
                                    ->visibleOn('edit')
                                    ->collapsible(),
                            ])
                            ->visibleOn('edit'),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString()
                    ->activeTab(1),
            ]);
    }
}
