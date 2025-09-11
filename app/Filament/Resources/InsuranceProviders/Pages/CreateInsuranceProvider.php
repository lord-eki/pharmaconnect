<?php

namespace App\Filament\Resources\InsuranceProviders\Pages;

use App\Filament\Resources\InsuranceProviders\InsuranceProviderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInsuranceProvider extends CreateRecord
{
    protected static string $resource = InsuranceProviderResource::class;

    protected static bool $canCreateAnother = false;

}
