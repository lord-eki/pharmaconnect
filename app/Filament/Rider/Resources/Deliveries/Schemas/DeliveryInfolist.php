<?php

namespace App\Filament\Rider\Resources\Deliveries\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class DeliveryInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('delivery_number'),
                TextEntry::make('order.id'),
                TextEntry::make('rider.id'),
                TextEntry::make('pickup_latitude')
                    ->numeric(),
                TextEntry::make('pickup_longitude')
                    ->numeric(),
                TextEntry::make('delivery_latitude')
                    ->numeric(),
                TextEntry::make('delivery_longitude')
                    ->numeric(),
                TextEntry::make('estimated_distance_km')
                    ->numeric(),
                TextEntry::make('delivery_fee')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('scheduled_pickup')
                    ->dateTime(),
                TextEntry::make('actual_pickup')
                    ->dateTime(),
                TextEntry::make('estimated_delivery')
                    ->dateTime(),
                TextEntry::make('actual_delivery')
                    ->dateTime(),
                TextEntry::make('recipient_name'),
                TextEntry::make('recipient_phone'),
                TextEntry::make('proof_of_delivery'),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ]);
    }
}
