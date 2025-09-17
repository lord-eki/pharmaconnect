<?php

namespace App\Filament\Resources\Deliveries\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DeliveryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Delivery Details')->schema([
                TextInput::make('delivery_number')
                    ->required(),
                Select::make('order_id')
                    ->relationship('order', 'id')
                    ->required(),
                Select::make('rider_id')
                    ->relationship('rider', 'id')
                    ->default(null),
                Textarea::make('pickup_address')
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('delivery_address')
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('pickup_latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('pickup_longitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('delivery_latitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('delivery_longitude')
                    ->numeric()
                    ->default(null),
                TextInput::make('estimated_distance_km')
                    ->numeric()
                    ->default(null),
                TextInput::make('delivery_fee')
                    ->required()
                    ->numeric(),
                Select::make('status')
                    ->options([
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'picked_up' => 'Picked up',
            'in_transit' => 'In transit',
            'delivered' => 'Delivered',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ])
                    ->default('pending')
                    ->required(),
                DateTimePicker::make('scheduled_pickup'),
                DateTimePicker::make('actual_pickup'),
                DateTimePicker::make('estimated_delivery'),
                DateTimePicker::make('actual_delivery'),
                Textarea::make('delivery_notes')
                    ->default(null)
                    ->columnSpanFull(),
                TextInput::make('recipient_name')
                    ->default(null),
                TextInput::make('recipient_phone')
                    ->tel()
                    ->default(null),
                TextInput::make('proof_of_delivery')
                    ->default(null),
                ])->columns(2)->columnSpanFull()
            ]);
    }
}
