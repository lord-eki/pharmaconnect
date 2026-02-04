<?php

namespace App\Filament\Resources\Orders\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_number'),
                TextEntry::make('quotation.quotation_number'),
                TextEntry::make('supplier.company_name'),
                TextEntry::make('prescription.prescription_number'),
                TextEntry::make('total_amount')
                    ->numeric()->money('KES'),
                
                TextEntry::make('status'),
                TextEntry::make('ordered_at')
                    ->dateTime(),
                TextEntry::make('sent_to_supplier_at')
                    ->dateTime(),
           
                TextEntry::make('created_at')
                    ->dateTime()->label('Date'),
           
            ])->columns(5);
    }
}
