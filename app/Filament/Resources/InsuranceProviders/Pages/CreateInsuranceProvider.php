<?php

namespace App\Filament\Resources\InsuranceProviders\Pages;

use App\Filament\Resources\InsuranceProviders\InsuranceProviderResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\User;

class CreateInsuranceProvider extends CreateRecord
{
    protected static string $resource = InsuranceProviderResource::class;

    protected static bool $canCreateAnother = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

    $data['user_id'] = User::where('name', $data['company_name'])->value('id');

        return $data;
    }
}
