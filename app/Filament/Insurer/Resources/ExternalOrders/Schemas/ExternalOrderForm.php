<?php

namespace App\Filament\Insurer\Resources\ExternalOrders\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ExternalOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('order_number')
                    ->label('Order Number')
                    ->required()
                    ->maxLength(255),
                TextInput::make('customer_name')
                    ->label('Customer Name')
                    ->required()
            ]);
    }
}
