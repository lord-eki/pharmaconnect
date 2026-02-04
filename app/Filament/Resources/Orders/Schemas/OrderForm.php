<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->required(),
                Select::make('quotation_id')
                    ->relationship('quotation', 'id')
                    ->default(null),
                Select::make('supplier_id')
                    ->relationship('supplier', 'id')
                    ->required(),
                Select::make('prescription_id')
                    ->relationship('prescription', 'id')
                    ->default(null),
                Select::make('external_order_id')
                    ->relationship('externalOrder', 'id')
                    ->default(null),
                TextInput::make('total_amount')
                    ->required()
                    ->numeric(),
                TextInput::make('supplier_total')
                    ->numeric()
                    ->default(null),
                TextInput::make('markup_total')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'pending_review' => 'Pending review',
            'confirmed' => 'Confirmed',
            'sent_to_supplier' => 'Sent to supplier',
            'processing' => 'Processing',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('ordered_at')
                    ->required(),
                DateTimePicker::make('sent_to_supplier_at'),
                DateTimePicker::make('expected_delivery'),
                DateTimePicker::make('delivered_at'),
                Textarea::make('notes')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
