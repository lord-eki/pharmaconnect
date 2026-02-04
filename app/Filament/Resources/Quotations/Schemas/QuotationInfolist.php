<?php

namespace App\Filament\Resources\Quotations\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('quotation_number'),
                TextEntry::make('prescription.prescription_number'),
                TextEntry::make('total_amount')
                    ->numeric()->money('KES'),
                TextEntry::make('status'),
                TextEntry::make('valid_until')
                    ->dateTime(),
                TextEntry::make('created_at')
                    ->dateTime(),
                TextEntry::make('updated_at')
                    ->dateTime(),
            ])->columns(4);
    }
}
