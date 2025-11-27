<?php

namespace App\Filament\Insurer\Resources\Insurance\PricingCatalogues\Pages;

use App\Filament\Insurer\Resources\Insurance\PricingCatalogues\PricingCatalogueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPricingCatalogue extends EditRecord
{
    protected static string $resource = PricingCatalogueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
