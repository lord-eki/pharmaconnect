<?php

namespace App\Filament\Physician\Resources\Documents\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('document_number')
                                ->label('Document Number')
                                ->disabled()
                                ->dehydrated(false),

                            Select::make('category_id')
                                ->label('Category')
                                ->relationship('category', 'name')
                                ->required()
                                ->searchable()
                                ->preload(),

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
                                    'other' => 'Other',
                                ])
                                ->required()
                                ->searchable(),

                            TextInput::make('title')
                                ->label('Title')
                                ->required()
                                ->maxLength(255),
                        ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),

                        FileUpload::make('file_path')
                            ->label('Upload Document')
                            ->disk('local')
                            ->directory('documents')
                            ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                            ->maxSize(5120) // 5MB
                            ->required()
                            ->downloadable()
                            ->previewable()
                            ->columnSpanFull()
                            ->afterStateUpdated(function ($state, Set $set) {
                                if ($state && is_object($state) && method_exists($state, 'getClientOriginalName')) {
                                    $set('file_name', $state->getClientOriginalName());
                                    $set('mime_type', $state->getMimeType());
                                    $set('file_size', $state->getSize());
                                    $set('file_hash', hash_file('sha256', $state->getRealPath()));
                                }
                            }),

                       
                    ]),

                Section::make('Related Entities')
                    ->description('Link this document to related records')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('prescription_id')
                                ->label('Prescription')
                                ->relationship('prescription', 'prescription_number')
                                ->searchable()
                                ->preload(),

                            Select::make('order_id')
                                ->label('Order')
                                ->relationship('order', 'order_number')
                                ->searchable()
                                ->preload(),

                            Select::make('insurance_claim_id')
                                ->label('Insurance Claim')
                                ->relationship('insuranceClaim', 'claim_number')
                                ->searchable()
                                ->preload(),

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
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->patient_number} - {$record->first_name} {$record->last_name}")
                                ->preload(),
                        ]),
                    ])
                    ->collapsible(),

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
                                ->required(),

                            Toggle::make('is_locked')
                                ->label('Lock Document')
                                ->helperText('Locked documents cannot be edited or deleted')
                                ->default(false),
                        ]),
                    ])
                    ->visibleOn('edit'),

                Section::make('Verification')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('verification_status')
                                ->label('Verification Status')
                                ->options([
                                    'pending' => 'Pending',
                                    'verified' => 'Verified',
                                    'rejected' => 'Rejected',
                                ])
                                ->default('pending')
                                ->required(),

                            DateTimePicker::make('verified_at')
                                ->label('Verified At')
                                ->disabled()
                                ->dehydrated(false),
                        ]),

                        Textarea::make('verification_notes')
                            ->label('Verification Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->visibleOn('edit'),

            ]);
    }
}
