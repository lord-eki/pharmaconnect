<?php

namespace App\Filament\Insurer\Resources\Insurance\InsurerOrders\Schemas;

use App\Models\Medicine;
use App\Models\Patient;
use App\Services\PricingService;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InsurerOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->disabled()
                            ->visible(fn ($record) => $record !== null),
                            
                        Select::make('patient_id')
                            ->label('Patient')
                            ->relationship('prescription.patient', 'last_name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(fn ($record) => $record !== null)
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $patient = Patient::find($state);
                                    $set('insurance_number', $patient->insurance_number ?? null);
                                }
                            }),
                            
                        TextInput::make('insurance_number')
                            ->label('Policy Number')
                            ->disabled()
                            ->dehydrated(false),
                            
                        Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->default('pending')
                            ->disabled(fn ($record) => $record && in_array($record->status, ['delivered', 'cancelled'])),
                    ])
                    ->columns(2),

                Section::make('Order Items')
                    ->schema([
                        Repeater::make('items')
                            ->relationship('items')
                            ->schema([
                                Select::make('medicine_id')
                                    ->label('Medicine')
                                    ->options(function () {
                                        return Medicine::active()
                                            ->withStock(1)
                                            ->get()
                                            ->mapWithKeys(fn ($med) => [$med->id => $med->display_name]);
                                    })
                                    ->searchable()
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($state) {
                                            $medicine = Medicine::find($state);
                                            $quantity = $get('quantity') ?? 1;
                                            $supplierPrice = $medicine->getCheapestSupplierPrice($quantity) ?? 0;
                                            
                                            $pricingService = app(PricingService::class);
                                            $pricing = $pricingService->calculateFinalPrice($supplierPrice, $medicine, $quantity);
                                            
                                            $set('supplier_price', $pricing['supplier_price']);
                                            $set('unit_price', $pricing['final_unit_price']);
                                            $set('total_price', $pricing['final_total']);
                                        }
                                    })
                                    ->disabled(fn ($record) => $record !== null),
                                    
                                TextInput::make('quantity')
                                    ->numeric()
                                    ->required()
                                    ->default(1)
                                    ->minValue(1)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $medicineId = $get('medicine_id');
                                        if ($medicineId && $state) {
                                            $medicine = Medicine::find($medicineId);
                                            $supplierPrice = $medicine->getCheapestSupplierPrice($state) ?? 0;
                                            
                                            $pricingService = app(PricingService::class);
                                            $pricing = $pricingService->calculateFinalPrice($supplierPrice, $medicine, $state);
                                            
                                            $set('supplier_price', $pricing['supplier_price']);
                                            $set('unit_price', $pricing['final_unit_price']);
                                            $set('total_price', $pricing['final_total']);
                                        }
                                    })
                                    ->disabled(fn ($record) => $record !== null),
                                    
                                TextInput::make('supplier_price')
                                    ->label('Supplier Price')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true),
                                    
                                TextInput::make('unit_price')
                                    ->label('Unit Price')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true),
                                    
                                TextInput::make('total_price')
                                    ->label('Total')
                                    ->prefix('KES')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true),
                            ])
                            ->columns(5)
                            ->defaultItems(1)
                            ->addActionLabel('Add Medicine')
                            ->disabled(fn ($record) => $record !== null)
                            ->collapsible(),
                    ]),

                Section::make('Financial Summary')
                    ->schema([
                        Placeholder::make('supplier_total')
                            ->label('Supplier Total')
                            ->content(fn ($record) => $record ? 
                                'KES ' . number_format($record->supplier_total, 2) : 
                                'KES 0.00'
                            ),
                        Placeholder::make('markup_total')
                            ->label('Markup Total')
                            ->content(fn ($record) => $record ? 
                                'KES ' . number_format($record->markup_total, 2) : 
                                'KES 0.00'
                            ),
                        Placeholder::make('total_amount')
                            ->label('Order Total')
                            ->content(fn ($record) => $record ? 
                                'KES ' . number_format($record->total_amount, 2) : 
                                'KES 0.00'
                            ),
                    ])
                    ->columns(3)
                    ->visible(fn ($record) => $record !== null),

                Section::make('Linked Claim')
                    ->schema([
                        Placeholder::make('claim_info')
                            ->label('Insurance Claim')
                            ->content(function ($record) {
                                if (!$record || !$record->prescription->insuranceClaim) {
                                    return 'No claim linked';
                                }
                                
                                $claim = $record->prescription->insuranceClaim;
                                return new \Illuminate\Support\HtmlString(
                                    '<strong>' . $claim->claim_number . '</strong><br>' .
                                    'Status: ' . ucfirst($claim->status) . '<br>' .
                                    'Claimed: KES ' . number_format($claim->claimed_amount, 2) . '<br>' .
                                    'Approved: KES ' . number_format($claim->approved_amount ?? 0, 2)
                                );
                            }),
                    ])
                    ->visible(fn ($record) => $record !== null)
                    ->collapsed(),

                Section::make('Notes')
                    ->schema([
                        Textarea::make('notes')
                            ->rows(3),
                    ])
                    ->collapsed(),
            ]);
    }
}
