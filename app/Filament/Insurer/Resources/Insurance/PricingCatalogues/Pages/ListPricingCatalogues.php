<?php

namespace App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Pages;

use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\PricingCatalogueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPricingCatalogues extends ListRecords
{
    protected static string $resource = PricingCatalogueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // CreateAction::make(),
        ];
    }
}
