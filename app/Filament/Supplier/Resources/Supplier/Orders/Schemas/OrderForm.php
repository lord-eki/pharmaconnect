<?php

namespace App\Filament\Supplier\Resources\Supplier\Orders\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                 Section::make('Order Information')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order Number (LPO)')
                            ->disabled()
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->columnSpan(1),

                        DatePicker::make('expected_delivery')
                            ->label('Expected Delivery Date')
                            ->minDate(now())
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Section::make('Prescription & Customer Details')
                    ->schema([
                        Placeholder::make('prescription_info')
                            ->label('Prescription Number')
                            ->content(fn ($record) => $record?->prescription 
                                ? $record->prescription->prescription_number
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

                        Placeholder::make('delivery_address')
                            ->label('Delivery Address')
                            ->content(fn ($record) => $record?->prescription?->patient?->address ?? 'N/A')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Section::make('Order Items')
                    ->schema([
                        Placeholder::make('items_list')
                            ->label('')
                            ->content(function ($record) {
                                if (!$record || !$record->orderItems) {
                                    return 'No items';
                                }

                                $html = '<div class="space-y-2">';
                                foreach ($record->orderItems as $item) {
                                    $medicine = $item->medicine;
                                    $html .= '<div class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">';
                                    $html .= '<div>';
                                    $html .= '<div class="font-semibold">' . ($medicine ? $medicine->generic_name : 'N/A') . '</div>';
                                    $html .= '<div class="text-sm text-gray-600">Quantity: ' . $item->quantity . ' @ KES ' . number_format($item->unit_price, 2) . '</div>';
                                    $html .= '</div>';
                                    $html .= '<div class="font-bold">KES ' . number_format($item->total_price, 2) . '</div>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ]),

                Section::make('Notes & Updates')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Order Notes')
                            ->maxLength(1000)
                            ->columnSpanFull()
                            ->placeholder('Add any notes about this order (shipping details, delays, etc.)'),
                    ])
                    ->collapsible(),
            ]);
    }
}
