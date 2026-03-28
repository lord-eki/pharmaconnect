<?php

namespace App\Filament\Supplier\Resources\Supplier\Inventories\Schemas;

use App\Models\Medicine;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Auth;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicine Information')
                    ->schema([
                        Select::make('medicine_id')
                            ->label('Medicine')
                            ->relationship(
                                name: 'medicine',
                                titleAttribute: 'generic_name',
                                modifyQueryUsing: fn ($query, $record) => $query
                                    ->when(
                                        !$record,
                                        fn ($q) => $q->whereDoesntHave('supplierMedicines', function ($subQuery) {
                                            // Get current supplier's ID
                                            $supplierId = Auth::user()->supplier?->id;
                                            if ($supplierId) {
                                                $subQuery->where('supplier_id', $supplierId);
                                            }
                                        })
                                    )->where('is_active', true)->orderBy('generic_name')
                            )
                            ->searchable(['generic_name', 'brand_name'])
                            ->preload()->required()->disabled(fn($operation) => $operation === 'edit')
                            ->getOptionLabelFromRecordUsing(fn (Medicine $record) => 
                                "{$record->generic_name} - {$record->brand_name} ({$record->strength})"
                            )
                            ->helperText('Only medicines not yet in your inventory are shown')
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & Stock')
                    ->schema([
                        TextInput::make('unit_price')
                            ->label('Unit Price (KES)')
                            ->required()
                            ->numeric()
                            ->prefix('KES')
                            ->minValue(0)
                            ->step(0.01)
                            ->live(onBlur: true),

                        TextInput::make('stock_quantity')
                            ->label('Stock Quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Current available stock'),

                        TextInput::make('minimum_order_quantity')
                            ->label('Minimum Order Qty')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),

                        Toggle::make('is_available')
                            ->label('Available for Orders')
                            ->default(true)
                            ->helperText('Toggle off to temporarily hide from quotations'),
                    ])
                    ->columns(2),

                Section::make('Batch Information')
                    ->schema([
                        TextInput::make('batch_number')
                            ->label('Batch Number')
                            ->maxLength(255),

                        DatePicker::make('expiry_date')
                            ->label('Expiry Date')
                            ->minDate(now())
                            ->helperText('Medicine expiration date'),

                        DateTimePicker::make('last_updated')
                            ->label('Last Stock Update')
                            ->default(now())
                            ->disabled()
                            ->dehydrated(),
                    ])
                    ->columns(3),

                Section::make('Additional Information')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}