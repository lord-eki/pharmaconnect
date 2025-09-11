<?php

namespace App\Filament\Resources\InsuranceProviders\Pages;

use App\Filament\Resources\InsuranceProviders\InsuranceProviderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInsuranceProviders extends ListRecords
{
    protected static string $resource = InsuranceProviderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->icon('heroicon-o-plus-circle')->label(' Add Insurance Details'),
        ];
    }
}
