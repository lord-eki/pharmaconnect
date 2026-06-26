<?php

namespace App\Filament\Operation\Resources\Medicines\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use App\Services\PricingService;

class MedicineForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Medicine Details')
                ->columnSpanFull()
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
                      Placeholder::make('cheapest_price')
                        ->label('Price')
                        ->content(function ($record) {
                            if (!$record) return 'N/A';

                            $supplierPrice = $record->getCheapestSupplierPrice(1);

                            if (!$supplierPrice) return 'N/A';

                            $pricing = app(PricingService::class)
                                ->calculateFinalPrice((float) $supplierPrice, $record, 1);

                            return 'KES ' . number_format($pricing['final_unit_price'], 2);
                        }),
                    ])->columns(4),

             
            ]);
    }
}
