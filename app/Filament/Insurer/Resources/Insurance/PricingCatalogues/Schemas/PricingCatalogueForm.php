<?php

namespace App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PricingCatalogueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicine Details')
                    ->schema([
                        TextInput::make('generic_name')
                            ->label('Generic Name')
                            ->disabled(),
                        TextInput::make('brand_name')
                            ->label('Brand Name')
                            ->disabled(),
                        TextInput::make('strength')
                            ->disabled(),
                        TextInput::make('dosage_form')
                            ->label('Dosage Form')
                            ->disabled(),
                    ])
                    ->columns(2),

                Section::make('Pricing Information')
                    ->schema([
                        Placeholder::make('cheapest_price')
                            ->label('Lowest Market Price')
                            ->content(fn ($record) => $record ? 
                                'KES ' . number_format($record->getCheapestSupplierPrice(1) ?? 0, 2) : 
                                'N/A'
                            ),
                        Placeholder::make('supplier_count')
                            ->label('Available Suppliers')
                            ->content(fn ($record) => $record ? 
                                $record->getAvailableSuppliers(1)->count() . ' supplier(s)' : 
                                '0'
                            ),
                    ])
                    ->columns(2),
            ]);
    }
}
