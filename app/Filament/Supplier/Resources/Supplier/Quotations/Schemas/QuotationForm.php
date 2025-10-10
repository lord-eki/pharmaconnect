<?php

namespace App\Filament\Supplier\Resources\Supplier\Quotations\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Quotation Information')
                    ->schema([
                        TextInput::make('quotation_number')
                            ->label('Quotation Number')
                            ->disabled()
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'sent' => 'Sent',
                                'accepted' => 'Accepted',
                                'rejected' => 'Rejected',
                                'expired' => 'Expired',
                            ])
                            ->disabled()
                            ->columnSpan(1),

                        DatePicker::make('valid_until')
                            ->label('Valid Until')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('Prescription Details')
                    ->schema([
                        Placeholder::make('prescription_info')
                            ->label('Prescription')
                            ->content(fn ($record) => $record?->prescription 
                                ? "RX: {$record->prescription->prescription_number}"
                                : 'N/A'
                            ),

                        Placeholder::make('physician_info')
                            ->label('Physician')
                            ->content(fn ($record) => $record?->prescription?->physician 
                                ? $record->prescription->physician->name 
                                : 'N/A'
                            ),

                        Placeholder::make('patient_info')
                            ->label('Patient')
                            ->content(fn ($record) => $record?->prescription?->patient 
                                ? "{$record->prescription->patient->first_name} {$record->prescription->patient->last_name}" 
                                : 'N/A'
                            ),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Your Quotation Items')
                    ->schema([
                        Repeater::make('quotationItems')
                            ->relationship('items')
                            ->schema([
                                Placeholder::make('medicine_name')
                                    ->label('Medicine')
                                    ->content(fn ($record) => $record?->supplierMedicine?->medicine 
                                        ? "{$record->supplierMedicine->medicine->generic_name} - {$record->supplierMedicine->medicine->brand_name}"
                                        : 'N/A'
                                    ),

                                TextInput::make('quantity')
                                    ->label('Requested Qty')
                                    ->disabled()
                                    ->numeric(),

                                TextInput::make('unit_price')
                                    ->label('Your Unit Price (KES)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('KES')
                                    ->minValue(0)
                                    ->step(0.01)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $get) {
                                        $quantity = $get('quantity') ?? 0;
                                        $set('total_price', $state * $quantity);
                                    }),

                                TextInput::make('total_price')
                                    ->label('Total Price')
                                    ->disabled()
                                    ->numeric()
                                    ->prefix('KES')
                                    ->dehydrated(),

                                Toggle::make('available')
                                    ->label('Can Supply?')
                                    ->default(true)
                                    ->helperText('Toggle off if you cannot supply this item'),

                                Textarea::make('notes')
                                    ->label('Notes')
                                    ->maxLength(500)
                                    ->columnSpanFull()
                                    ->placeholder('Add any special notes (e.g., delivery time, alternative options)'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->cloneable(false)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->columnSpanFull(),
                    ]),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('General Notes')
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder('Add any general notes about this quotation'),
                    ])
                    ->collapsible(),
            ]);
    }
}
