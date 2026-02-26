<?php

namespace App\Filament\Operation\Resources\InsuranceClaims\Pages;

use App\Filament\Operation\Resources\InsuranceClaims\InsuranceClaimResource;
use Filament\Resources\Pages\CreateRecord;

class CreateInsuranceClaim extends CreateRecord
{
    protected static string $resource = InsuranceClaimResource::class;
}
