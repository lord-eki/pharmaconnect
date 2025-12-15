<?php

namespace App\Filament\Operation\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                    ->description('Basic order details and current status')
                    ->schema([
                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->disabled()
                            ->dehydrated(false),
                        
                        Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending_review' => 'Pending Review',
                                'sent_to_supplier' => 'Sent to Supplier',
                                'confirmed' => 'Confirmed',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required()
                            ->disabled(fn ($record) => $record && in_array($record->status, ['delivered', 'cancelled']))
                            ->helperText(fn ($record) => $record && in_array($record->status, ['delivered', 'cancelled']) 
                                ? 'Status cannot be changed for delivered or cancelled orders' 
                                : 'Select the current order status'),
                        
                        DateTimePicker::make('expected_delivery')
                            ->label('Expected Delivery Date')
                            ->helperText('When do you expect this order to be delivered?'),
                        
                        Textarea::make('notes')
                            ->label('Internal Notes')
                            ->rows(4)
                            ->columnSpanFull()
                            ->helperText('These notes are for internal use only'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Related Information')
                    ->description('Linked prescription and supplier details')
                    ->schema([
                        Select::make('prescription_id')
                            ->relationship('prescription', 'prescription_number')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('The prescription this order is for'),
                        
                        Select::make('supplier_id')
                            ->relationship('supplier', 'name')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('The supplier fulfilling this order'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Section::make('Financial Details')
                    ->description('Order amounts and pricing breakdown')
                    ->schema([
                        TextInput::make('supplier_total')
                            ->label('Supplier Total')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Amount paid to supplier'),
                        
                        TextInput::make('markup_total')
                            ->label('Markup Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Our markup on this order'),
                        
                        TextInput::make('total_amount')
                            ->label('Total Amount')
                            ->numeric()
                            ->prefix('KES')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Final amount charged to patient'),
                    ])
                    ->columns(3)
                    ->collapsible(),
            ]);
    }
}
